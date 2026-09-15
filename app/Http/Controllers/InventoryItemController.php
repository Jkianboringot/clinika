<?php

namespace App\Http\Controllers;

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
    public function index(Request $request): Response
{
    $items = InventoryItem::query()
        ->when(
            $request->input('search'),
            fn ($query, $search) =>
                $query->where('item_name', 'like', "%{$search}%")
        )
        ->orderBy('item_name')
        ->paginate(15)
        ->withQueryString();

    return Inertia::render('Items/Index', [
        'inventoryitems' => $items,
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

        InventoryItem::create($validated);

        return redirect()->route('inventory_items.index')->with('message', 'InventoryItem created successfully.');
    }

    public function edit(InventoryItem $inventoryItem): Response
    {
        // $this->authorize('items.view');

        return Inertia::render('Items/Edit', [
            'inventoryItem' => $inventoryItem->only(['id', 'item_name', 'description', 'minimum_stock']),
        ]);
    }

    public function update(InventoryItemRequest $request, InventoryItem $inventoryItem): RedirectResponse
    {
        // $this->authorize('items.update');

        $validated = $request->validated();

        $inventoryItem->update($validated);

        return redirect()->route('inventory_items.index')->with('message', 'InventoryItem updated successfully.');
    }

    public function destroy(InventoryItem $inventoryItem): RedirectResponse
    {
        // $this->authorize('items.delete');

        $inventoryItem->delete();

        return redirect()->route('inventory_items.index')->with('message', 'InventoryItem deleted successfully.');
    }

}
