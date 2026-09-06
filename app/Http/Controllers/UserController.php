<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Http\Requests\SearchRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    public function create()
    {
        $this->authorize('create', User::class);

        return Inertia::render('Users/Create', []);
    }

    public function store(UserRequest $request)
    {
        $this->authorize('create', User::class);

        $request->validated();

        User::create($request->all());
        return redirect()->route('users.index')->with('message', 'User Created Successfully');
    }

    public function destroy(Request $request)
    {
        $user = User::findOrFail($request->id);
        $this->authorize('delete', $user);

        $user->deleteOrFail();
        return redirect()->route('users.index')->with('message', 'User Deleted Successfully');
    }

    public function edit(Request $request)
    {
        $p = User::findOrFail($request->id);
        $this->authorize('view', $p);

        return Inertia::render('Users/Edit', ['users' => $p]);
    }

    public function update(UserRequest $request)
    {
        $p = User::findOrFail($request->id);
        $this->authorize('update', $p);

        $request->validated();
        $p->update($request->all());

        return redirect()->route('users.index')->with('message', 'User Updated Successfully');
    }

    public function index(SearchRequest $request)
    {
        $this->authorize('viewAny', User::class);

        $request->validated();
        $users = User::query()
            ->when($request->string('search')->trim(), function ($query, $search) {
                $query->where('name', 'like', "{$search}%");
            })
            ->orderBy('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search']),
        ]);
    }
}