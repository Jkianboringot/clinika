<?php

namespace App\Services;

use App\Exceptions\TreatmentLimitExceededException;
use App\Models\Appointment;
use App\Models\InventoryItem;
use App\Models\Treatment;
use App\Models\TreatmentPrescriptionDetails;
use App\Enums\AppointmentStatusEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TreatmentService
{
    /**
     * Create a treatment for an appointment.
     * 1. lock appointment  2. save treatment  3. save prescriptions
     * 4. reserve/reduce stock  5. mark appointment completed
     */
    function treatmentStore(Appointment $ap, array $data)
    {
        try {

            return DB::transaction(
                function () use ($ap, $data) {

                    // Lock the appointment row first so two simultaneous requests
                    // can't both read "0 existing treatments" and both proceed.
                    $lockedAppointment = Appointment::where('id', $ap->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    // Business rule: one Appointment has exactly one Treatment.
                    $this->assertTreatmentAllowed($lockedAppointment->id);

                    $tr = new Treatment();
                    $tr->fill($data['treatment']);
                    $tr->appointment_id = $lockedAppointment->id;
                    $tr->user_id = auth()->id();
                    $tr->save();

                    foreach ($data['prescriptionlist'] as $pl) {
                        $tpdl = new TreatmentPrescriptionDetails();
                        $tpdl->fill($pl);
                        $tpdl->treatment_id = $tr->id;
                        $tpdl->save();
                    }

                    // Stock check + reservation (row locks on each InventoryItem).
                    $lowStockWarnings = $this->reserveStockOrFail($data['itemList']);

                    foreach ($data['itemList'] as $listItem) {
                        $tr->treatmentInventories()->create([
                            'inventory_item_id' => $listItem['item_id'],
                            'quantity' => $listItem['quantity'],
                        ]);
                    }

                    $lockedAppointment->status = AppointmentStatusEnum::Completed->value;
                    $lockedAppointment->save();

                    // Warnings ride along on the model so the controller can flash
                    // them without aborting the transaction.
                    $tr->setAttribute('low_stock_warnings', $lowStockWarnings);

                    return $tr;
                }
            );

        } catch (\Throwable $th) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            // Business-rule / validation failures are expected, not bugs.
            if (
                !($th instanceof \Illuminate\Validation\ValidationException) &&
                !($th instanceof \Illuminate\Auth\Access\AuthorizationException) &&
                !($th instanceof TreatmentLimitExceededException) &&
                !($th instanceof \RuntimeException)
            ) {
                Log::error('Treatment store failed unexpectedly', [
                    'appointment_id' => $ap->id,
                    'exception' => $th,
                ]);
            }

            throw $th;
        }
    }


    /**
     * Update a treatment.
     * FIX: prescriptions are now replaced (delete + recreate) instead of the old
     *      TreatmentPrescriptionDetails::findOrFail($tr->id), which looked up by the
     *      TREATMENT id and hit the wrong row (or none).
     * FIX: items removed from the list are now deleted so their stock is returned.
     */
    function treatmentUpdate(Treatment $tr, array $data)
    {
        try {

            return DB::transaction(
                function () use ($tr, $data) {

                    // Lock the parent appointment so an update can't race a delete.
                    Appointment::where('id', $tr->appointment_id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $tr->fill($data['treatment']);
                    $tr->user_id = auth()->id();
                    $tr->save();

                    // The edit form sends the full prescription list without row ids,
                    // so replace the whole set.
                    $tr->perscriptions()->delete();
                    foreach ($data['prescriptionlist'] as $pl) {
                        $tpdl = new TreatmentPrescriptionDetails();
                        $tpdl->fill($pl);
                        $tpdl->treatment_id = $tr->id;
                        $tpdl->save();
                    }

                    // Stock check against the DELTA between old and new quantity.
                    $lowStockWarnings = $this->reserveStockDeltaOrFail($tr, $data['itemList']);

                    $keepItemIds = collect($data['itemList'])->pluck('item_id')->unique()->all();

                    // Items no longer in the list -> remove the row, which returns stock.
                    $tr->treatmentInventories()
                        ->whereNotIn('inventory_item_id', $keepItemIds)
                        ->delete();

                    foreach ($data['itemList'] as $listItem) {
                        $tr->treatmentInventories()->updateOrCreate(
                            ['inventory_item_id' => $listItem['item_id']],
                            ['quantity' => $listItem['quantity']]
                        );
                    }

                    $tr->setAttribute('low_stock_warnings', $lowStockWarnings);

                    return $tr;
                }
            );

        } catch (\Throwable $th) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            if (
                !($th instanceof \Illuminate\Validation\ValidationException) &&
                !($th instanceof \Illuminate\Auth\Access\AuthorizationException) &&
                !($th instanceof \RuntimeException)
            ) {
                Log::error('Treatment update failed unexpectedly', [
                    'treatment_id' => $tr->id,
                    'exception' => $th,
                ]);
            }

            throw $th;
        }
    }


    /**
     * Enforces the 1 Appointment : 1 Treatment rule. Call ONLY after the
     * appointment row is locked in the surrounding transaction.
     *
     * @throws TreatmentLimitExceededException
     */
    private function assertTreatmentAllowed(int $appointmentId): void
    {
        $existingCount = Treatment::where('appointment_id', $appointmentId)->count();

        if ($existingCount >= 1) {
            throw TreatmentLimitExceededException::limitReached($appointmentId);
        }
    }


    /**
     * Like reserveStockOrFail(), but checks the CHANGE in quantity per item.
     *
     * delta > 0  => needs MORE of this item => must fit in stock
     * delta <= 0 => needs LESS/same => always fine
     *
     * @return string[] low-stock warning messages
     */
    private function reserveStockDeltaOrFail(Treatment $tr, array $itemList): array
    {
        $warnings = [];

        $itemIds = collect($itemList)->pluck('item_id')->unique()->sort()->values();

        $lockedItems = InventoryItem::whereIn('id', $itemIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        // Quantities already reserved by THIS treatment, keyed by item id.
        $existingQuantities = $tr->treatmentInventories()
            ->whereIn('inventory_item_id', $itemIds)
            ->pluck('quantity', 'inventory_item_id');

        foreach ($itemList as $listItem) {
            $itemId = $listItem['item_id'];
            $newQty = round((float) $listItem['quantity'], 2);
            $oldQty = round((float) ($existingQuantities[$itemId] ?? 0), 2);

            $delta = round($newQty - $oldQty, 2);

            $item = $lockedItems->get($itemId);

            if (!$item) {
                throw new \RuntimeException("Inventory item #{$itemId} was not found.");
            }

            $stockAdditionTotal = (float) $item->inventoryStock()->sum('quantity');
            $treatmentTotal = (float) $item->treatmentInventories()->sum('quantity');
            // Balance already INCLUDES this treatment's old reservation.
            $currentStock = round($stockAdditionTotal - $treatmentTotal, 2);

            $resultingStock = round($currentStock - $delta, 2);

            if ($delta > 0 && $resultingStock < 0) {
                throw new \RuntimeException(
                    "Insufficient stock for \"{$item->item_name}\": " .
                    "needs {$delta} more, but only {$currentStock} available."
                );
            }

            if ($item->minimum_stock !== null && $resultingStock < (float) $item->minimum_stock) {
                $warnings[] = "\"{$item->item_name}\" will drop to {$resultingStock}, " .
                    "below its minimum stock of {$item->minimum_stock}.";
            }
        }

        return $warnings;
    }


    /**
     * Locks each involved InventoryItem row, recomputes real (unclamped) stock,
     * and validates the deduction. Must run inside a DB::transaction().
     *
     * - Resulting stock < 0 => throws (rolls back the whole transaction).
     * - Resulting stock < minimum_stock (but >= 0) => returned as a warning.
     *
     * @return string[] low-stock warning messages
     */
    private function reserveStockOrFail(array $itemList): array
    {
        $warnings = [];

        // Sorted IDs => consistent lock order => no deadlocks.
        $itemIds = collect($itemList)->pluck('item_id')->unique()->sort()->values();

        $lockedItems = InventoryItem::whereIn('id', $itemIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($itemList as $listItem) {
            $itemId = $listItem['item_id'];
            $requestedQty = round((float) $listItem['quantity'], 2);

            $item = $lockedItems->get($itemId);

            if (!$item) {
                throw new \RuntimeException("Inventory item #{$itemId} was not found.");
            }

            // Raw balance, NOT ->stock() (which clamps to 0).
            $stockAdditionTotal = (float) $item->inventoryStock()->sum('quantity');
            $treatmentTotal = (float) $item->treatmentInventories()->sum('quantity');
            $currentStock = round($stockAdditionTotal - $treatmentTotal, 2);

            $resultingStock = round($currentStock - $requestedQty, 2);

            if ($resultingStock < 0) {
                throw new \RuntimeException(
                    "Insufficient stock for \"{$item->item_name}\": " .
                    "available {$currentStock}, requested {$requestedQty}."
                );
            }

            if ($item->minimum_stock !== null && $resultingStock < (float) $item->minimum_stock) {
                $warnings[] = "\"{$item->item_name}\" will drop to {$resultingStock}, " .
                    "below its minimum stock of {$item->minimum_stock}.";
            }
        }

        return $warnings;
    }


    /**
     * FIX: this method was called by the controller but didn't exist.
     * Read-only: describes what deleting this treatment would do.
     *
     * @return array{
     *   treatment_id:int, appointment_id:int, prescriptions_removed:int,
     *   items_returned: array<int, array{item_id:int, name:string|null, quantity:float}>,
     *   appointment_status_after:string, warnings:string[]
     * }
     */
    function treatmentDeletePreview(Treatment $treatment): array
    {
        return $this->buildDeleteImpact($treatment);
    }


    /**
     * Delete flow:
     * 1. treatment_inventories rows ARE the stock deduction; deleting them returns stock
     * 2. prescriptions + treatment are deleted
     * 3. appointment status -> Cancelled
     *
     * FIX: now returns the impact array (with a 'warnings' key) instead of `true`,
     *      so the controller's $impact['warnings'] works.
     *
     * @return array same shape as treatmentDeletePreview()
     */
    function treatmentDelete(Treatment $treatment): array
    {
        try {

            return DB::transaction(function () use ($treatment) {

                $appointment = Appointment::where('id', $treatment->appointment_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Build the impact BEFORE deleting anything.
                $impact = $this->buildDeleteImpact($treatment);

                // Explicit delete even though the FK cascades, so the outcome
                // doesn't silently depend on the migration.
                $treatment->perscriptions()->delete();
                $treatment->treatmentInventories()->delete();
                $treatment->delete();

                $appointment->status = AppointmentStatusEnum::Cancelled->value;
                $appointment->save();

                return $impact;
            });

        } catch (\Throwable $th) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            if (
                !($th instanceof \Illuminate\Validation\ValidationException) &&
                !($th instanceof \Illuminate\Auth\Access\AuthorizationException)
            ) {
                Log::error('Treatment delete failed unexpectedly', [
                    'treatment_id' => $treatment->id,
                    'exception' => $th,
                ]);
            }

            throw $th;
        }
    }


    /**
     * Shared by preview + delete so they can never disagree.
     */
    private function buildDeleteImpact(Treatment $treatment): array
    {
        $inventories = $treatment->treatmentInventories()->get();

        $names = InventoryItem::whereIn('id', $inventories->pluck('inventory_item_id'))
            ->pluck('item_name', 'id');

        return [
            'treatment_id' => $treatment->id,
            'appointment_id' => $treatment->appointment_id,
            'prescriptions_removed' => $treatment->perscriptions()->count(),
            'items_returned' => $inventories->map(fn($ti) => [
                'item_id' => $ti->inventory_item_id,
                'name' => $names[$ti->inventory_item_id] ?? null,
                'quantity' => (float) $ti->quantity,
            ])->values()->all(),
            'appointment_status_after' => AppointmentStatusEnum::Cancelled->value,
            // Returning stock can't cause low-stock problems, so this is normally empty.
            'warnings' => [],
        ];
    }
}