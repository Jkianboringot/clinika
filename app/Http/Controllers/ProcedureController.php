<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Http\Requests\ProcedureRequest;
use App\Models\Procedure;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProcedureController extends Controller
{
    public function index(SearchRequest $request): Response
    {
        // TODO: add this later
        // $this->authorize('procedures.view');

        $validated = $request->validated();

        $procedures = Procedure::query()
            ->when(
                $validated['search'] ?? null,
                fn($query, $search) =>
                    $query->where('name', 'like', "%{$search}%")
            )
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Procedures/Index', [
            'procedures' => $procedures,
            'filters' => [
                'search' => $validated['search'] ?? '',
            ],
        ]);
    }

    public function create(): Response
    {
        // $this->authorize('procedures.create');

        return Inertia::render('Procedures/Create');
    }

    public function store(ProcedureRequest $request): RedirectResponse
    {
        // $this->authorize('procedures.create');


        Procedure::create($request->validated());

        return redirect()->route('procedures.index')->with('message', 'Procedure created successfully.');
    }

    public function edit(Procedure $procedure): Response
    {
        // $this->authorize('procedures.view');

        return Inertia::render('Procedures/Edit', [
            'procedure' => $procedure->only(['id', 'name']),
        ]);
    }

    public function update(ProcedureRequest $request, Procedure $procedure): RedirectResponse
    {
        // $this->authorize('procedures.update');


        $procedure->update($request->validated());

        return redirect()->route('procedures.index')->with('message', 'Procedure updated successfully.');
    }

    public function destroy(Procedure $procedure): RedirectResponse
    {
        // $this->authorize('procedures.delete');
        $procedure->deleteOrFail();

        return redirect()->route('procedures.index')->with('message', 'Procedure deleted successfully.');
    }
}