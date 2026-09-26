<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Http\Requests\ProcedureRequest;
use App\Models\Procedure;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;


class ProcedureController extends Controller
{
    public function index(Request $request): Response
    {
        // TODO: add this later
        // $this->authorize('procedures.view');


        $procedures = Procedure::query()
            ->when(
                $request->input('search'),
                fn($query, $search) =>
                    $query->where('name', 'like', "%{$search}%")
            )
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Procedures/Index', [
            'procedures' => $procedures,
            'filters' => [
                'search' => $request->input('search'),
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
            'procedure' => $procedure,
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

        if ($procedure->appointment()->exists()) {
            return redirect()
                ->route('procedures.index')
                ->with('error', 'This Procedure use in appointment and cannot be deleted.');
        }
        try {


            $procedure->deleteOrFail();

        } catch (\Throwable $e) {
            return redirect()->route('procedures.index')->with('error', 'Failed to delete procedure');
        }

        return redirect()->route('procedures.index')->with('message', 'Procedure deleted successfully.');
    }
}