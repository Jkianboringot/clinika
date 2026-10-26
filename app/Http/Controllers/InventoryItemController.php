<?php

namespace App\Http\Controllers;

use App\Enums\StockStatusEnum;
use App\Http\Requests\InventoryItemRequest;
use App\Http\Requests\SearchRequest;
use App\Models\InventoryItem;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;

use Illuminate\Http\RedirectResponse;

/**
 * =====================================================================
 * INVENTORY ITEMS MODULE — Controller Notes
 * =====================================================================
 * Type   : Product — Backend Logic
 * Purpose: Simple CRUD for clinic inventory items/supplies. No service
 *          layer needed (same pattern as Appointment) — logic here
 *          is minimal.
 * =====================================================================
 *
 * ACCESS / ROLES
 * ---------------
 * - Only STAFF can access this module at all.
 * - Within staff/Admin: only staff/Admin role can Create, Edit, and Delete.
 *   (Regular staff cannot perform these actions.)
 *
 * NATURE OF THIS MODULE
 * ----------------------
 * - Straightforward CRUD, no complex business logic.
 * - No dedicated Service class — Controller handles it directly.
 *
 * DATA FIELDS
 * -----------
 * - item_name
 * - minimum_stock
 *
 * NOT INCLUDED (on purpose)
 * --------------------------
 * - price/cost — not the system's responsibility as of now.
 *
 * FIELD PURPOSE
 * --------------
 * - minimum_stock: used as the threshold to determine when an items
 *   is considered "low/minimum stock". This is a business rule in
 *   concept, but not something this module actively enforces —
 *   it just stores the value for other parts of the system to use.
 *
 * ARCHITECTURE FLOW
 * ------------------
 *   UI -> Route -> Controller -> Request Form (validation)
 *
 *   NOTE: Wrap operations in try/catch for error handling.
 *
 * BUSINESS LOGIC
 * ---------------
 * 1. Unique name enforcement
 *    - Items name must be unique, no duplicates allowed.
 *
 * 2. Delete restriction
 *    - Cannot delete an items if it's connected to something else
 *      (e.g. a treatment referencing it).
 *
 * 3. Update safeguard
 *    - If the items does not exist, stop the operation immediately
 *      (e.g. return 404) — do not proceed further.
 *
 * TESTING
 * --------
 * - Not detailed here — this file is only a short intro/reference.
 * - [REMINDER] Tests SHOULD exist for this module.
 *   See test files for full coverage.
 *
 * =====================================================================
 */

class InventoryItemController extends Controller
{

    public function index(Request $request)
    {
        $items = InventoryItem::withStockTotals()
            ->when($request->search, fn($q, $s) => $q->where('item_name', 'like', "%{$s}%"))
            // optional: ?status=low|critical|in_stock
            ->when($request->status, fn($q, $s) => $q->whereStockStatus(StockStatusEnum::from($s)))
            ->orderBy('item_name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn(InventoryItem $item) => [
                'id' => $item->id,
                'item_name' => $item->item_name,
                'minimum_stock' => $item->minimum_stock,
                'critical_stock' => $item->critical_stock,
                'stock' => $item->current_stock,
                'stock_status' => $item->stock_status->value,
                'stock_status_label' => $item->stock_status->label(),
            ]);

        return Inertia::render('Items/Index', [

            'inventoryitems' => $items,
            'lowStockItems' => InventoryItem::lowStockItems(),
            'filters' => $request->only('search', 'status'),
        ]);
    }
    public function create(): Response
    {
        // $this->authorize('items.create');

        return Inertia::render('Items/Create');
    }

    public function store(InventoryItemRequest $request): RedirectResponse
    {
        // $this->authorize('items.create');

        $validated = $request->validated();

        try {
            InventoryItem::create($validated);
        } catch (\Throwable $e) {
            return redirect()
                ->route('inventory_items.index')
                ->with('error', 'Failed to create Inventory Item.');
        }

        return redirect()->route('inventory_items.index')->with('message', 'Inventory Item created successfully.');
    }

    public function edit(InventoryItem $inventoryItem): Response
    {
        // $this->authorize('items.view');

        return Inertia::render('Items/Edit', [
            'inventoryItem' => $inventoryItem->only(['id', 'item_name', 'description', 'minimum_stock', 'critical_stock']),
        ]);
    }

    public function update(InventoryItemRequest $request, InventoryItem $inventoryItem): RedirectResponse
    {
        // $this->authorize('items.update');

        $validated = $request->validated();

        try {
            $inventoryItem->update($validated);
        } catch (\Throwable $e) {
            return redirect()
                ->route('inventory_items.index')
                ->with('error', 'Failed to update Inventory Item.');
        }

        return redirect()->route('inventory_items.index')->with('message', 'Inventory Item updated successfully.');
    }

    public function destrody(InventoryItem $inventoryItem): RedirectResponse
    {
        // $this->authorize('items.delete');

        $inventoryItem->delete();

        return redirect()->route('inventory_items.index')->with('message', 'Inventory Item deleted successfully.');
    }



    public function destroy(InventoryItem $inventoryItem): RedirectResponse
    {

        if ($inventoryItem->inventoryStock()->exists()) {
            return redirect()
                ->route('inventory_items.index')
                ->with('error', 'This Inventory Item has stock and cannot be deleted.');
        }


        if ($inventoryItem->treatmentInventories()->exists()) {
            return redirect()
                ->route('inventory_items.index')
                ->with('error', 'This Inventory Item use in treatment and cannot be deleted.');
        }

        try {
            $inventoryItem->deleteOrFail();

        } catch (\Throwable $e) {
            return redirect()->route('inventory_items.index')->with('error', 'Failed to delete Inventory Item');
        }

        return redirect()->route('inventory_items.index')->with('message', 'Inventory Item deleted successfully.');
    }

}