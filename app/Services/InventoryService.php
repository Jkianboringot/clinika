<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryService
{
    function inventoryStore(array $data)
    {
        try {
            return DB::transaction(function () use ($data) {
                $tr = new Inventory();
                $tr->staff_id = auth()->id();
                $tr->save();

                foreach ($data['itemList'] as $listItem) {
                    $tr->inventoryStock()->create([
                        'inventory_item_id' => $listItem['item_id'],
                        'quantity' => $listItem['quantity'],
                    ]);
                }

                return $tr;
            });
        } catch (\Throwable $th) {
            $this->logUnexpected('Inventory store failed unexpectedly', $th);
            throw $th;
        }
    }


    /**
     * Update = sync. Items removed from the list are deleted, quantities are
     * changed, new items are added. The stock check runs on the DELTA per item.
     */
    function inventoryUpdate(Inventory $inv, array $data)
    {
        try {
            return DB::transaction(function () use ($inv, $data) {

                // Lock the parent row so two edits/deletes of the same entry are sequential.
                Inventory::where('id', $inv->id)->lockForUpdate()->firstOrFail();

                // Same behaviour as updateOrCreate: if an item is listed twice, last one wins.
                $newQuantities = collect($data['itemList'])
                    ->keyBy('item_id')
                    ->map(fn($row) => round((float) $row['quantity'], 2));

                $oldQuantities = $this->currentQuantities($inv);

                // Check every item touched: old ones (maybe removed) + new ones.
                $allItemIds = $oldQuantities->keys()->merge($newQuantities->keys())->unique();

                $deltas = $allItemIds->mapWithKeys(fn($id) => [
                    $id => round(($newQuantities[$id] ?? 0) - ($oldQuantities[$id] ?? 0), 2),
                ]);

                $warnings = $this->checkStockChangeOrFail($deltas);

                $inv->staff_id = auth()->id();
                $inv->save();

                $inv->inventoryStock()
                    ->whereNotIn('inventory_item_id', $newQuantities->keys()->all())
                    ->delete();

                foreach ($newQuantities as $itemId => $qty) {
                    $inv->inventoryStock()->updateOrCreate(
                        ['inventory_item_id' => $itemId],
                        ['quantity' => $qty]
                    );
                }

                $inv->setAttribute('low_stock_warnings', $warnings);

                return $inv;
            });
        } catch (\Throwable $th) {
            $this->logUnexpected('Inventory update failed unexpectedly', $th);
            throw $th;
        }
    }


    /**
     * Read-only: what would deleting this entry do to stock?
     */
    function inventoryDeletePreview(Inventory $inv): array
    {
        return DB::transaction(function () use ($inv) {
            $deltas = $this->currentQuantities($inv)->map(fn($qty) => round(-$qty, 2));

            return $this->buildImpact($inv, $deltas, $this->checkStockChangeOrFail($deltas, false));
        });
    }


    /**
     * Delete the entry. Every row's quantity is removed from stock, so this
     * throws if any item would go negative.
     */
    function inventoryDelete(Inventory $inv): array
    {
        try {
            return DB::transaction(function () use ($inv) {

                Inventory::where('id', $inv->id)->lockForUpdate()->firstOrFail();

                $deltas = $this->currentQuantities($inv)->map(fn($qty) => round(-$qty, 2));

                $warnings = $this->checkStockChangeOrFail($deltas);
                $impact = $this->buildImpact($inv, $deltas, $warnings);

                $inv->inventoryStock()->delete();
                $inv->delete();

                return $impact;
            });
        } catch (\Throwable $th) {
            $this->logUnexpected('Inventory delete failed unexpectedly', $th);
            throw $th;
        }
    }


    /**
     * Total quantity this entry currently holds, per item id.
     * (Summed in case old bugs left duplicate rows for the same item.)
     */
    private function currentQuantities(Inventory $inv)
    {
        return $inv->inventoryStock()->get()
            ->groupBy('inventory_item_id')
            ->map(fn($rows) => round((float) $rows->sum('quantity'), 2));
    }


    /**
     * Locks each involved InventoryItem, computes the real (unclamped) balance,
     * and applies the stock change. Must run inside DB::transaction().
     *
     * @param  \Illuminate\Support\Collection $deltas item_id => change in stock
     *         (negative = stock goes down, positive = stock goes up)
     * @return string[] low-stock warnings (before/after included)
     * @throws \RuntimeException when any item would end up below 0
     */
    private function checkStockChangeOrFail($deltas, bool $lock = true): array
    {
        $warnings = [];

        // Sorted IDs => consistent lock order => no deadlocks.
        $itemIds = $deltas->keys()->sort()->values();

        $query = InventoryItem::whereIn('id', $itemIds);
        if ($lock) {
            $query->lockForUpdate();
        }
        $items = $query->get()->keyBy('id');

        foreach ($itemIds as $itemId) {
            $delta = $deltas[$itemId];
            $item = $items->get($itemId);

            if (!$item) {
                throw new \RuntimeException("Inventory item #{$itemId} was not found.");
            }

            // Raw balance, NOT ->stock() (which clamps to 0).
            $additions = (float) $item->inventoryStock()->sum('quantity');
            $used = (float) $item->treatmentInventories()->sum('quantity');
            $before = round($additions - $used, 2);
            $after = round($before + $delta, 2);

            if ($delta < 0 && $after < 0) {
                $needed = abs($delta);
                throw new \RuntimeException(
                    "Cannot complete this change for \"{$item->item_name}\": " .
                    "it would remove {$needed}, but only {$before} is available " .
                    "(the rest has already been used)."
                );
            }

            // Only warn when stock is going DOWN and lands under the minimum.
            if (
                $delta < 0 &&
                $item->minimum_stock !== null &&
                $after < (float) $item->minimum_stock
            ) {
                $warnings[] = "\"{$item->item_name}\" is below minimum stock: " .
                    "before {$before}, after {$after} (minimum {$item->minimum_stock}).";
            }
        }

        return $warnings;
    }


    private function buildImpact(Inventory $inv, $deltas, array $warnings): array
    {
        $names = InventoryItem::whereIn('id', $deltas->keys())->pluck('item_name', 'id');

        return [
            'inventory_id' => $inv->id,
            'items_removed' => $deltas->map(fn($delta, $id) => [
                'item_id' => $id,
                'name' => $names[$id] ?? null,
                'quantity' => abs($delta),
            ])->values()->all(),
            'warnings' => $warnings,
        ];
    }


    private function logUnexpected(string $message, \Throwable $th): void
    {
        if (
            !($th instanceof \Illuminate\Validation\ValidationException) &&
            !($th instanceof \Illuminate\Auth\Access\AuthorizationException) &&
            !($th instanceof \RuntimeException)
        ) {
            Log::error($message, ['exception' => $th]);
        }
    }
}