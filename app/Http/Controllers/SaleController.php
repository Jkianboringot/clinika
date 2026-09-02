<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SaleController extends Controller
{

    // public function create()
    // {
    //     return Inertia::render('Sales/Create', []);
    // }

    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'name' => 'required',
    //         'price' => 'required',
    //     ]);

    //     Sale::create($request->all());
    //     return redirect()->route('sales.index')->with('message', 'Sale Created Successfully');
    // }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => ['required', 'exists:sales,id']
        ]);

        Sale::findOrFail($request->id)->deleteOrFail();
        return redirect()->route('sales.index')->with('message', 'Sale Delete Successfully');
    }

    public function edit(Request $request)
    {
        $p = Sale::findOrFail($request->id);

        // $request->validate([
        //     'name' => 'required',
        //     'price' => 'required',
        // ]);


        return Inertia::render('Sales/Edit', ['sales' => $p]);

    }

    public function update(Request $request)
    {

        $p = Sale::findOrFail($request->id);

        $request->validate([
            'name' => 'required',
            'price' => 'required',
        ]);


        $p->update($request->all());

        return redirect()->route('sales.index')->with('message', 'Sale Delete Successfully');

    }

    // ASK-YOURSELF - ask about which of this two is better the top update or this below one
    //   public function update(Request $request,Sale $sale)
    // {


    //     $request->validate([
    //         'name' => 'required',
    //         'price' => 'required',
    //     ]);

    //     $sale->update([
    //         'name'=>$sale->input('name'),
    //         'price'=>$sale->input('price'),
    //     ]);

    //     return redirect()->route('sales.index')->with('message', 'Sale Delete Successfully');

    // }
// SaleController.php

    public function index()
    {
        $sale = Sale::with(['branch', 'encoder', 'inventory'])
            ->latest()
            ->paginate(15)
            ->through(fn(Sale $sl) => [
                'id' => $sl->id,
                'branch' => $sl->branch?->name,
                'inventory' => $sl->inventory?->id,
                'encoder' => $sl->encoder?->name,
                'cash_amount' => $sl->cash_amount,
                'shift' => $sl->shift,
                'cash_advance' => $sl->cash_advance,
                'cash_shortage' => $sl->cash_shortage,
                'remitted_expenses' => $sl->remitted_expenses,
                'gcash_amount' => $sl->gcash_amount,
                'net_cash' => $sl->net_cash,
                'created_at' => $sl->created_at->format('M d, Y g:i A'),
            ]);


        return Inertia::render('Sales/Index', ['sales' => $sale]);
    }
}
