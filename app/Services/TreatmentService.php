<?php

namespace App\Services;

use App\Enums\AppointmentStatusEnum;
use App\Exceptions\TreatmentLimitExceededException;
use App\Models\Appointment;
use App\Models\InventoryItem;
use App\Models\Treatment;
use App\Models\TreatmentPrescriptionDetails;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TreatmentService
{
    /**
     * Only the dentist assigned to the appointment may treat it.
     * Read-only, so the controller can call it before showing the page.
     *
     * @throws \RuntimeException
     */
    public function assertOwnership(Appointment $appointment, ?int $dentistId): void
    {
        if ($dentistId === null || (int) $appointment->dentist_id !== $dentistId) {
            throw new \RuntimeException('Only the dentist assigned to this appointment can treat it.');
        }
    }

    /**
     * An appointment can be treated only when the clinic has approved it and
     * it is still open (not cancelled, not already completed).
     *
     * @throws \RuntimeException
     */
    public function assertOpenForTreatment(Appointment $appointment): void
    {
        if ($appointment->is_approve !== 'approved') {
            throw new \RuntimeException('Only an approved appointment can be treated.');
        }

        $status = $appointment->status instanceof \BackedEnum
            ? $appointment->status->value
            : $appointment->status;

        if (in_array($status, [
            AppointmentStatusEnum::Cancelled->value,
            AppointmentStatusEnum::Completed->value,
        ], true)) {
            throw new \RuntimeException('This appointment is cancelled or already completed.');
        }
    }

    /**
     * Create a treatment for an appointment.
     * 1. lock appointment  2. ownership + one-treatment + approval checks
     * 3. save treatment  4. save prescriptions  5. reserve stock
     * 6. save items  7. mark appointment completed
     */
    public function treatmentStore(Appointment $ap, array $data)
    {
        try {
            return DB::transaction(function () use ($ap, $data) {
                $locked = Appointment::where('id', $ap->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->assertOwnership($locked, auth()->id());
                $this->assertTreatmentAllowed($locked->id);
                $this->assertOpenForTreatment($locked);
                $this->assertHasItems($data['itemList']);

                $tr = new Treatment();
                $tr->fill($data['treatment']);
                $tr->appointment_id = $locked->id;
                $tr->user_id = auth()->id();
                $tr->save();

                foreach ($data['prescriptionlist'] as $pl) {
                    $tpdl = new TreatmentPrescriptionDetails();
                    $tpdl->fill($pl);
                    $tpdl->treatment_id = $tr->id;
                    $tpdl->save();
                }

                $lowStockWarnings = $this->reserveStockOrFail($data['itemList']);

                foreach ($data['itemList'] as $listItem) {
                    $tr->treatmentInventories()->create([
                        'inventory_item_id' => $listItem['item_id'],
                        'quantity' => $listItem['quantity'],
                    ]);
                }

                $locked->status = AppointmentStatusEnum::Completed->value;
                $locked->save();

                $tr->setAttribute('low_stock_warnings', $lowStockWarnings);

                return $tr;
            });
        } catch (\Throwable $th) {
            $this->logUnexpected('Treatment store failed unexpectedly', $th, [
                'appointment_id' => $ap->id,
            ]);

            throw $th;
        }
    }

    /**
     * Update a treatment. The appointment is not re-checked for approval,
     * because it is already completed once the treatment exists. Only the
     * owning dentist and a non-empty item list are required.
     */
    public function treatmentUpdate(Treatment $tr, array $data)
    {
        try {
            return DB::transaction(function () use ($tr, $data) {
                $appointment = Appointment::where('id', $tr->appointment_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->assertOwnership($appointment, auth()->id());
                $this->assertHasItems($data['itemList']);

                $tr->fill($data['treatment']);
                $tr->user_id = auth()->id();
                $tr->save();

                // The edit form sends the full prescription list, so replace the whole set.
                $tr->perscriptions()->delete();
                foreach ($data['prescriptionlist'] as $pl) {
                    $tpdl = new TreatmentPrescriptionDetails();
                    $tpdl->fill($pl);
                    $tpdl->treatment_id = $tr->id;
                    $tpdl->save();
                }

                // Stock check on the change per item, not the full quantity.
                $lowStockWarnings = $this->reserveStockDeltaOrFail($tr, $data['itemList']);

                // Items no longer in the list: removing the row returns its stock.
                $keepItemIds = collect($data['itemList'])->pluck('item_id')->unique()->all();
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
            });
        } catch (\Throwable $th) {
            $this->logUnexpected('Treatment update failed unexpectedly', $th, [
                'treatment_id' => $tr->id,
            ]);

            throw $th;
        }
    }

    /**
     * A treatment needs at least one item.
     *
     * @throws \RuntimeException
     */
    private function assertHasItems(array $itemList): void
    {
        if (empty($itemList)) {
            throw new \RuntimeException('A treatment needs at least one item.');
        }
    }

    /**
     * 1 appointment : 1 treatment. Call only after the appointment row is locked.
     *
     * @throws TreatmentLimitExceededException
     */
    private function assertTreatmentAllowed(int $appointmentId): void
    {
        if (Treatment::where('appointment_id', $appointmentId)->exists()) {
            throw TreatmentLimitExceededException::limitReached($appointmentId);
        }
    }

    /**
     * Stock check for an update: only the CHANGE in quantity per item matters.
     * delta > 0 means more is needed, so it must fit in stock.
     *
     * @return string[] low-stock warnings
     */
    private function reserveStockDeltaOrFail(Treatment $tr, array $itemList): array
    {
        $warnings = [];

        $itemIds = collect($itemList)->pluck('item_id')->unique()->sort()->values();

        $lockedItems = InventoryItem::whereIn('id', $itemIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

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

            // Balance already includes this treatment's old usage.
            $currentStock = round(
                (float) $item->inventoryStock()->sum('quantity')
                - (float) $item->treatmentInventories()->sum('quantity'),
                2
            );

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
     * Stock check for a new treatment. Locks each item, then checks the balance.
     *
     * @return string[] low-stock warnings
     */
    private function reserveStockOrFail(array $itemList): array
    {
        $warnings = [];

        // Sorted IDs give a consistent lock order, which avoids deadlocks.
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

            $currentStock = round(
                (float) $item->inventoryStock()->sum('quantity')
                - (float) $item->treatmentInventories()->sum('quantity'),
                2
            );

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
     * Expected failures (RuntimeException, validation, authorization) are not logged.
     * Anything else is logged, then rethrown.
     */
    private function logUnexpected(string $message, \Throwable $th, array $context = []): void
    {
        if (
            !($th instanceof \RuntimeException) &&
            !($th instanceof \Illuminate\Validation\ValidationException) &&
            !($th instanceof \Illuminate\Auth\Access\AuthorizationException)
        ) {
            Log::error($message, $context + ['exception' => $th]);
        }
    }
}