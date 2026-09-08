<?php

namespace App\Http\Controllers;

use App\Enums\InOutType;
use App\Enums\Shift;
use App\Enums\StockMovementType;
use App\Http\Requests\StoreInInventoryRequest;
use App\Http\Requests\StoreInventoryRequest;
use App\Http\Requests\StoreOutInventoryRequest;
use App\Models\Branch;
use App\Models\Inventory;
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
 *          Stock REDUCTION is handled elsewhere (e.g. treatment
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

class InventoryController extends Controller
{
    public function __construct(protected InventoryService $inventoryService)
    {
    }

    /**
     * List all inventory records (both IN and OUT), same data the
     * Filament table showed.
     */
    public function index(): Response
    {
        $inventories = Inventory::with(['branchs', 'encoder', 'sales'])
            ->latest()
            ->paginate(15)
            ->through(fn(Inventory $inv) => [
                'id' => $inv->id,
                'type' => $inv->type,
                'inventory_type' => $inv->inventory_type,
                'stock_movement_type' => $inv->stock_movement_type,
                'branch' => $inv->branchs?->location,
                'encoder' => $inv->encoder?->name,
                'cash_amount' => $inv->sales?->cash_amount,
                'net_cash' => $inv->sales?->net_cash,
                'created_at' => $inv->created_at->format('M d, Y g:i A'),
            ]);

        return Inertia::render('Inventories/Index', [
            'inventories' => $inventories,
        ]);
    }

    /**
     * Show the "Stock In" form. Product list is open to every item —
     * a delivery can introduce a new item to a branch.
     */
    public function createIn(): Response
    {
        return Inertia::render('Inventories/CreateIn', [

            'stockMovementTypes' => collect(StockMovementType::cases())->map(fn($cases) => ['value' => $cases->value, 'label' => Str::headline($cases->name)]),
            'branches' => Branch::select('id', 'location', 'branch_type')->get(),
            'products' => Product::select('id', 'name', 'price')->get(),
        ]);
    }

    public function storeIn(StoreInInventoryRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $inv = $this->inventoryService->inventoryIn([
            'branch_id' => $data['branch_id'],
            'inventory' => [
                'stock_movement_type' => $data['stock_movement_type'],

            ],
            'productList' => $data['productList'],
        ]);

        if (!$inv) {
            return back()->with('error', 'Failed to record stock in. Check the logs.');
        }

        return redirect()
            ->route('inventories.index')
            ->with('success', 'Stock in recorded.');
    }

    /**
     * Show the "Stock Out" form. Sends every branch along with the
     * products it already carries (with current quantity), so the
     * frontend can filter/display stock without extra requests.
     */
    public function createOut(): Response
    {
        $branches = Branch::with('products')->get()->map(fn(Branch $branch) => [
            'id' => $branch->id,
            'location' => $branch->location,
            'products' => $branch->products->map(fn(Product $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'quantity' => $item->pivot->quantity,
            ]),
        ]);

        return Inertia::render('Inventories/CreateOut', [
            'shifts' => collect(Shift::cases())->map(fn($cases) => ['value' => $cases->value, 'label' => Str::headline($cases->name)]),

            'stockMovementTypes' => collect(StockMovementType::cases())->map(fn($cases) => ['value' => $cases->value, 'label' => Str::headline($cases->name)]),

            'branches' => $branches,
        ]);
    }

    public function storeOut(StoreOutInventoryRequest $request): RedirectResponse
    {
        // dd($request);
        $data = $request->validated();

        $inv = $this->inventoryService->inventoryOut([
            'branch_id' => $data['branch_id'],
            'inventory' => [
                'stock_movement_type' => $data['stock_movement_type'],

            ],
            'productList' => $data['productList'],
            'sale' => [
                'shift' => $data['shift'],
                'cash_amount' => $data['cash_amount'],
                'gcash_amount' => $data['gcash_amount'],
                'cash_advance' => $data['cash_advance'],
                'remitted_expenses' => $data['remitted_expenses'],
                'cash_shortage' => $data['cash_shortage'] ?? 0,
                'net_cash' => $data['net_cash'],
            ],
        ]);

        if (!$inv) {
            return back()->with('error', 'Failed to record sale. Check the logs.');
        }

        return redirect()
            ->route('inventories.index')
            ->with('success', 'Sale recorded.');
    }
}