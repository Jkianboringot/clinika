<?php

namespace App\Http\Controllers;

use App\Enums\InOutType;
use App\Enums\Shift;
use App\Enums\StockMovementType;
use App\Http\Requests\InventoryItemRequest;
use App\Http\Requests\InventoryRequest;
use App\Http\Requests\StoreInInventoryRequest;
use App\Http\Requests\StoreInventoryRequest;
use App\Http\Requests\StoreOutInventoryRequest;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;
use Inertia\Response;
use Str;



/**
 * =====================================================================
 * INVENTORY MODULE — Controller / Service Notes
 * =====================================================================
 * Type   : Product — Backend Logic
 * Purpose: Responsible ONLY for increasing product stock.
 *          Stock REDUCTION is handled elsewhere (e.g. inventory
 *          equipment usage) — not this module's responsibility.
 * =====================================================================
 *
 * ACCESS / ROLES
 * ---------------
 * - Only STAFF and ADMIN can Create, Edit, and Delete.
 * - Staff and Admin responsibilities are the same here, but kept
 *   as separate implementations/paths.
 *
 * NATURE OF THIS MODULE
 * ----------------------
 * - Likely needs to be a SERVICE (not a plain controller) due to
 *   the stock-calculation business rules below.
 *
 * UI / DATA SHAPE
 * -----------------
 * - UI shows a "product list" — each product has a quantity input.
 * - Submitted as an ARRAY of { product_id, quantity } to the controller.
 *
 * VALIDATION RULES
 * -----------------
 * - product_id must exist in DB (protects against tampered/fake IDs).
 * - quantity:
 *     - must be an INTEGER (no decimals/floats).
 *     - min: 1
 *     - max: 999
 *
 * BUSINESS LOGIC
 * ---------------
 *
 * 
 *  no we will be using pattern 1 now, 
 * 1. CREATE (Adding stock) — "Pattern 2" (aggregate, not overwrite)
 *    - Check if the product already has an existing quantity in stock.
 *      - If YES  -> new_stock = current_quantity + incoming_quantity
 *      - If NO   -> new_stock = incoming_quantity (set directly)
 *    - MUST still lock the DB transaction here.
 *      Reason: even though Create is purely additive (no subtraction
 *      logic), two staff members could submit stock additions for the
 *      same product at nearly the same time. Without a lock, both
 *      requests could read the same "current stock" value before
 *      either write happens, causing one addition to silently
 *      overwrite/be lost instead of both being summed correctly.
 *    -note not purely additive since if YES sum add current and incomming, if no setdirectly so we are updating which can be reduce if we are not carefull
 *
 * 2. UPDATE (Editing a previous stock entry) — requires extra care
 *    - MUST lock the DB transaction here — this operation carries
 *      more risk than Create.
 *    - Race condition concern: if two people edit the same product
 *      at the same time (e.g. one adding stock, one reducing it),
 *      whoever's request finishes first "wins," and the second
 *      overwrite may be based on stale data. This can produce
 *      wrong stock counts — e.g. a late/backdated correction from
 *      one staff member conflicting with a real-time update from
 *      another. Locking ensures updates are applied sequentially
 *      against the true current value, not a stale read.
 *    - Logic: reverse the OLD value's effect, then apply the NEW value.
 *
 *      Example:
 *        - Original entry added: 10
 *        - Current total stock:  20  (includes other additions too)
 *        - Original entry edited from 10 -> 5
 *
 *        Step 1: base_stock = current_stock - original_quantity
 *                           = 20 - 10 = 10
 *        Step 2: new_stock  = base_stock + updated_quantity
 *                           = 10 + 5 = 15
 *
 *    - This ensures the update reflects only the DIFFERENCE of that
 *      specific entry, not a blind overwrite of total stock.
 *
 * 3. DELETE restrictions
 *    - Cannot delete an entry if quantity >= 1.
 *    - Cannot delete an entry if it has EVER been used — even if
 *      current quantity is 0 (0 could just mean "out of stock",
 *      not "safe to remove").
 *    - If product record is not found -> STOP the operation immediately.
 *      (No soft-fail handling for now — hard stop only.)
 *
 * =====================================================================
 */


// that is useless now since i will be using pattent 1

class InventoryController extends Controller
{
    public function __construct(protected InventoryService $inventoryService)
    {
    }

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        $inventories = Inventory::query()
            ->with(['staff', 'inventoryStock.items'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('staff', function ($staffQuery) use ($search) {
                        $staffQuery->where('name', 'like', "%{$search}%");
                    })
                        ->orWhereHas('inventoryStock.items', function ($itemQuery) use ($search) {
                            $itemQuery->where('item_name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn(Inventory $inventory) => [
                'id' => $inventory->id,
                'staff_name' => $inventory->staff?->name,
                'items' => $inventory->inventoryStock->map(fn($stock) => [
                    'id' => $stock->id,
                    'item_name' => $stock->items?->item_name,
                    'quantity' => $stock->quantity,
                ]),
                'created_at' => $inventory->created_at?->format('M d, Y'),
            ]);

        return Inertia::render('Inventories/Index', [
            'inventories' => $inventories,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    //this needs to start from appointment and all we need to do is click it and put as in create of inventory or we could just show it in drop-down 

    //this needs the appointment info such as patient and id of appointment,procedure, item, the inventory description is not needed since we dont need to show data on that,
    public function create()
    {


        return Inertia::render('Inventories/Create', [
            'items' => InventoryItem::selectRaw('id, item_name as name')->orderBy('item_name')->get(),
        ]);

    }


  public function store(InventoryRequest $request)
{
    try {
        $this->inventoryService->inventoryStore(['itemList' => $request->validated()['itemList']]);
    } catch (\Throwable $e) {
        return back()->with('error', 'Failed to create inventory');
    }

    return redirect()->route('inventories.index')->with('message', 'Successfully created inventory');
}
    public function edit(Inventory $inventory)
    {
        $inventory->load(['inventoryStock']);



        // i cant make that just return inventory=>$inventory but i honestly have no desired to chagne the ui stucture for nwo is no
        return Inertia::render('Inventories/Edit', [
            'inventory' => [
                'id' => $inventory->id,
                'staff_id' => $inventory->staff_id,
                'created_at' => $inventory->created_at,
                'itemList' => $inventory->inventoryStock->map(fn($ti) => [
                    'item_id' => $ti->inventory_item_id,
                    'quantity' => $ti->quantity,
                ]),

            ],
            'items' => InventoryItem::all()->map(fn($i) => [
                'id' => $i->id,
                'name' => $i->item_name,
            ]),

        ]);
    }



public function update(Inventory $inventory, InventoryRequest $request)
{
    try {
        $inv = $this->inventoryService->inventoryUpdate($inventory, [
            'itemList' => $request->validated()['itemList'],
        ]);
    } catch (\RuntimeException $e) {
        return back()->with('error', $e->getMessage());   // stock would go negative
    } catch (\Throwable $e) {
        return back()->with('error', 'Failed to update inventory');
    }

    return redirect()->route('inventories.index')
        ->with('message', 'Successfully updated inventory')
        ->with('warning', $inv->low_stock_warnings);
}

  
public function destroy(Inventory $inventory): RedirectResponse
{
    try {
        $impact = $this->inventoryService->inventoryDelete($inventory);
    } catch (\RuntimeException $e) {
        return redirect()->route('inventories.index')->with('error', $e->getMessage());
    } catch (\Throwable $e) {
        return redirect()->route('inventories.index')->with('error', 'Could not delete this inventory entry.');
    }

    return redirect()->route('inventories.index')
        ->with('message', 'Inventory entry deleted successfully.')
        ->with('warning', $impact['warnings']);
}
}