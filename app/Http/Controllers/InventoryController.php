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
     * Show the "Stock In" form. Product list is open to every product —
     * a delivery can introduce a new product to a branch.
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
            'products' => $branch->products->map(fn(Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'quantity' => $product->pivot->quantity,
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