<?php

namespace App\Services;

use App\Enums\InOutType;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\Sale;
use App\Models\Treatment;
use App\Models\TreatmentPrescriptionDetails;
use App\Enums\AppointmentStatusEnum;
use Error;
use ErrorException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use function Laravel\Prompts\error;


class TreatmentService
{
    // 1.reference the appointment✅
    // 2.save Treatment✅
    // 3.save treatment_prescription_details
    // 4.reduce stock 
    // 5.after all this are done , we edit appointment status to complete
    function treatmentStore(Appointment $ap, array $data)
    {

        try {

            return DB::transaction(
                function () use ($ap, $data) {


                    $tr = new Treatment();
                    $tr->fill($data['treatment']);
                    $tr->appointment_id = $ap->id;
                    $tr->user_id = auth()->id();
                    $tr->save();



                    // TODO(later): added check if medicine even exist, no harm in double security even if validation should already catch this
                    foreach ($data['prescriptionlist'] as $pl) {
                        // $m=$pl->findOrfial('medication_id');
                        $tpdl = new TreatmentPrescriptionDetails();
                        $tpdl->fill($pl);
                        $tpdl->treatment_id = $tr->id;
                        $tpdl->save();
                    }


                    // Stock check + reservation. Locks each InventoryItem row
                    // (SELECT ... FOR UPDATE) so concurrent treatments can't both
                    // read the same "current stock" and both pass the check.
                    $lowStockWarnings = $this->reserveStockOrFail($data['itemList']);

                    foreach ($data['itemList'] as $listItem) {
                        $tr->treatmentInventories()->create([
                            'inventory_item_id' => $listItem['item_id'],
                            'quantity' => $listItem['quantity'],
                        ]);
                    }


                    //why does this work here but not inmigration?
                    $ap->status = AppointmentStatusEnum::Completed->value;
                    $ap->save();

                    // Attach warnings so the controller can flash them without
                    // aborting the transaction — treatment still saves.
                    $tr->setAttribute('low_stock_warnings', $lowStockWarnings);

                    return $tr;


                }
            );

        } catch (\Throwable $th) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            //   ask what is this for
            if (
                !($th instanceof \Illuminate\Validation\ValidationException) &&
                !($th instanceof \Illuminate\Auth\Access\AuthorizationException)
            ) {
                Log::error($th);
            }

            // Re-throw so the controller can turn this into a flash('error', ...)
            // instead of silently swallowing an "insufficient stock" failure.
            throw $th;
        }



    }



  function treatmentUpdate(Treatment $tr, array $data)
{

    try {

        return DB::transaction(
            function () use ($tr, $data) {


                $tr->fill($data['treatment']);
                $tr->user_id = auth()->id();
                $tr->save();



                // TODO(later): added check if medicine even exist, no harm in double security even if validation should already catch this
                foreach ($data['prescriptionlist'] as $pl) {
                    // $m=$pl->findOrfial('medication_id');
                    $tpdl = TreatmentPrescriptionDetails::findOrFail($tr->id);
                    $tpdl->fill($pl);
                    $tpdl->save();
                }


                // Stock check against the DELTA between old and new quantity
                // per item — not the new quantity alone.
                $lowStockWarnings = $this->reserveStockDeltaOrFail($tr, $data['itemList']);

                foreach ($data['itemList'] as $listItem) {
                    $tr->treatmentInventories()->updateOrCreate(
                        ['inventory_item_id' => $listItem['item_id']],
                        ['quantity' => $listItem['quantity']]
                    );
                }
                //why does this work here but not inmigration?
                    // $ap->status = AppointmentStatusEnum::Completed->value;
                    // $ap->save();

                $tr->setAttribute('low_stock_warnings', $lowStockWarnings);

                return $tr;


            }
        );

    } catch (\Throwable $th) {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }

        //   ask what is this for
        if (
            !($th instanceof \Illuminate\Validation\ValidationException) &&
            !($th instanceof \Illuminate\Auth\Access\AuthorizationException)
        ) {
            Log::error($th);
        }

        throw $th;
    }



}


/**
 * Same locking strategy as reserveStockOrFail(), but checks the CHANGE
 * in quantity per item rather than the new quantity outright — since an
 * update replaces an existing treatment_inventories row rather than
 * adding a fresh deduction.
 *
 * delta > 0  => treatment now needs MORE of this item => must fit in stock
 * delta <= 0 => treatment now needs LESS (or the same) => always fine,
 *               frees stock up, never rejected
 *
 * @param array<int, array{item_id: int, quantity: float|int|string}> $itemList
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

    // Existing quantities already reserved by THIS treatment, keyed by item id.
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
        // Current true balance, INCLUDING this treatment's own old
        // reservation (since it's already subtracted in $treatmentTotal).
        $currentStock = round($stockAdditionTotal - $treatmentTotal, 2);

        // Only an increase in usage can push stock negative.
        $resultingStock = round($currentStock - $delta, 2);

        if ($delta > 0 && $resultingStock < 0) {
            throw new \RuntimeException(
                "Insufficient stock for \"{$item->item_name}\": ".
                "needs {$delta} more, but only {$currentStock} available."
            );
        }

        if ($item->minimum_stock !== null && $resultingStock < (float) $item->minimum_stock) {
            $warnings[] = "\"{$item->item_name}\" will drop to {$resultingStock}, ".
                "below its minimum stock of {$item->minimum_stock}.";
        }
    }

    return $warnings;
}


    /**
     * Locks each involved InventoryItem row, recomputes its real (unclamped)
     * stock, and validates the deduction. Must be called from inside an
     * existing DB::transaction() — lockForUpdate() only holds its lock for
     * the lifetime of the surrounding transaction.
     *
     * - Resulting stock < 0  => throws, which rolls back the whole transaction.
     * - Resulting stock < minimum_stock (but still >= 0) => collected as a
     *   warning string and returned, operation still proceeds.
     *
     * @param array<int, array{item_id: int, quantity: float|int|string}> $itemList
     * @return string[] low-stock warning messages
     */
    private function reserveStockOrFail(array $itemList): array
    {
        $warnings = [];

        // Sort item IDs before locking so concurrent requests always acquire
        // row locks in the same order — avoids a deadlock where two
        // transactions lock different rows first then wait on each other.
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

            // Raw balance, NOT the model's ->stock() (which clamps to 0 via
            // max(0, ...) — we need the unclamped number to detect the
            // resulting stock going negative).
            $stockAdditionTotal = (float) $item->inventoryStock()->sum('quantity');
            $treatmentTotal = (float) $item->treatmentInventories()->sum('quantity');
            $currentStock = round($stockAdditionTotal - $treatmentTotal, 2);

            $resultingStock = round($currentStock - $requestedQty, 2);

            if ($resultingStock < 0) {
                throw new \RuntimeException(
                    "Insufficient stock for \"{$item->item_name}\": ".
                    "available {$currentStock}, requested {$requestedQty}."
                );
            }

            if ($item->minimum_stock !== null && $resultingStock < (float) $item->minimum_stock) {
                $warnings[] = "\"{$item->item_name}\" will drop to {$resultingStock}, ".
                    "below its minimum stock of {$item->minimum_stock}.";
            }
        }

        return $warnings;
    }


    // when delte happens:
    // 1.reverse the reduction on stock by deleting the record of invnetoryItem or setting them to is_deleted, just let this auto since we are doing pattern 1 or live data
    // 2.appointment status cancelled
    // 3.prescriptionlist and treatment needs to be delete

    // test:
    // 1.when deleting we need to do db check if prescriptionlist is actually gone along with Treatment
    // 2.check current stock if it return, or in this case just check if inventoryItem is delete, since we have no way to know how many it is or rather is not needed 
    // 3.check if appointment status is updated properly
    function treatmentDelete(Treatment $treatment)
    {



        try {
            return DB::transaction(function () use ($treatment) {



            });
        } catch (\Throwable $th) {
            //throw $th;
        }
    }
}