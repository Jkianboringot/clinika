<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Http\Requests\SearchRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{

    public function create()
    {
        return Inertia::render('Products/Create', []);
    }

    public function store(ProductRequest $request)
    {
        $request->validated();

        Product::create($request->all());
        return redirect()->route('products.index')->with('message', 'Product Created Successfully');
    }

    public function delete(Request  $request)
    {
        // $request
        // dont really need validatoin here since we are just to check for id, if it fail dont 
        // do shit if we do find delete

        // make sure it cannot be delete if it has quantity
        //and the race condition consider that, you cannot delete shit that is delete by other
        Product::findOrFail($request->id)->deleteOrFail();
        return redirect()->route('products.index')->with('message', 'Product Delete Successfully');
    }

    public function edit(Request $request)
    {
        $p = Product::findOrFail($request->id);

        // $request->validate([
        //     'name' => 'required',
        //     'price' => 'required',
        // ]);


        return Inertia::render('Products/Edit', ['products' => $p]);

    }

    public function update(ProductRequest $request)
    {

        // i have this thing so i dont have to do validation exists on id, and i do this first
        //becuase if it finds something validate it if not dont even bother validating anything
        $p = Product::findOrFail($request->id);
        $request->validated(); 

        $p->update($request->all());

        return redirect()->route('products.index')->with('message', 'PRoduct Delete Successfully');

    }

    // ASK-YOURSELF - ask about which of this two is better the top update or this below one
    //   public function update(Request $request,Product $product)
    // {


    //     $request->validate([
    //         'name' => 'required',
    //         'price' => 'required',
    //     ]);

    //     $product->update([
    //         'name'=>$product->input('name'),
    //         'price'=>$product->input('price'),
    //     ]);

    //     return redirect()->route('products.index')->with('message', 'PRoduct Delete Successfully');

    // }

    public function index(SearchRequest $request)
    {
        $request->validated();
        $products = Product::query()
            ->when($request->string('search')->trim(), function ($query, $search) {
                $query->where('name', 'like', "{$search}%");
            })
            ->orderBy('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Products/Index', [
            'products' => $products,
            'filters' => $request->only(['search']),
        ]);
    }
}
