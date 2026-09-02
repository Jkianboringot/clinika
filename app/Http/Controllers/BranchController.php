<?php

namespace App\Http\Controllers;

use App\Enums\BranchType;
use App\Http\Requests\BranchRequest;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;
use Str;

class BranchController extends Controller
{

    public function delete(Request $request)
    {
        Branch::findOrFail($request->id)->deleteOrFail();
        return redirect()->route('branches.index')->with('message', 'Branch Delete Successfully');
    }


    public function create()
    {
        return Inertia::render('Branches/Create', [
            'branch_types' => collect(BranchType::cases())->map(fn($cases) => ['value' => $cases->value, 'label' => Str::headline($cases->name)]),

        ]);
    }

    public function store(BranchRequest $request)
    {
        $request->validated();

        Branch::create($request->all());
        return redirect()->route('branches.index')->with('message', 'Branch Created Successfully');
    }



    public function edit(Request $request)
    {
        $p = Branch::findOrFail($request->id);

        return Inertia::render('Branches/Edit', [
            'branches' => $p,
            'branch_types' => collect(BranchType::cases())->map(fn($cases) => ['value' => $cases->value, 'label' => Str::headline($cases->name)]),
        ]);

    }

    public function update(BranchRequest $request)
    {

        $p = Branch::findOrFail($request->id);

        $request->validated();


        $p->update($request->all());

        return redirect()->route('branches.index')->with('message', 'Branch Update Successfully');

    }

    // ASK-YOURSELF - ask about which of this two is better the top update or this below one
    //   public function update(Request $request,Branch $branch)
    // {


    //     $request->validate([
    //         'name' => 'required',
    //         'price' => 'required',
    //     ]);

    //     $branch->update([
    //         'name'=>$branch->input('name'),
    //         'price'=>$branch->input('price'),
    //     ]);

    //     return redirect()->route('branchs.index')->with('message', 'Branch Delete Successfully');

    // }
// BranchController.php
    public function products(Branch $branch)
    {
        $products = $branch->products()
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Branches/BranchProducts', [
            'branch' => $branch->only('id', 'location'),
            'products' => $products,
        ]);
    }
    public function index(Request $request)
    {
        $branches = Branch::withCount('products')
            ->when($request->string('search')->trim(), function ($query, $search) {
                $query->where('location', 'like', "%{$search}%");
            })
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Branches/Index', [
            'branches' => $branches,
            'filters' => $request->only(['search']),
        ]);
    }
}
