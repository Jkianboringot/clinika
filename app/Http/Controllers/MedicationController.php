<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Http\Requests\MedicationRequest;
use App\Models\Medication;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * =====================================================================
 * MEDICATION MODULE — Controller Notes
 * =====================================================================
 * Type   : Product — Backend Logic
 * Purpose: Manages the list of medications available for prescription.
 *          Acts as an editable "dropdown source" for the clinic —
 *          not a fixed pharmaceutical database, since not all medicine
 *          names are known ahead of time and the clinic should be able
 *          to add its own.
 * =====================================================================
 *
 * NATURE OF THIS MODULE
 * ----------------------
 * - Simple CRUD, no real business logic — except unique name check.
 * - not Stored as free text input as itis for dropdown, NOT an enum.
 *   Reason: medicine names/usage repeat often, so once created,
 *   the same entry gets reused instead of duplicated.
 *
 * DATA FIELDS
 * -----------
 * - medication_name   (e.g. "Amoxicillin")
 * - medication_dosage
 *
 * NOT INCLUDED (on purpose)
 * --------------------------
 * - frequency of use
 *   Reason: this is a clinic, not a pharmaceutical system —
 *   frequency should be tailored per patient, not fixed per medication.
 *
 * ARCHITECTURE FLOW
 * ------------------
 *   UI -> Route -> Controller -> Request Form (validation) -> Service
 *
 *   NOTE: Wrap creation logic in try/catch.
 *
 * BUSINESS LOGIC
 * ---------------
 * 1. Unique name enforcement
 *    - Medication name must be unique.
 *    - On create: check storage first.
 *      - If not found -> create.
 *      - If found -> stop immediately (return existing / reject duplicate).
 *
 * 2. [REMINDER] Delete restriction (not implemented yet)
 *    - Ideally: should not delete a medication if it's linked/referenced
 *      elsewhere (e.g. existing prescriptions).
 *    - NOT required for now — only add if explicitly requested later.
 *
 * =====================================================================
 */



class MedicationController extends Controller
{
    public function index(SearchRequest $request): Response
    {
        // $this->authorize('medications.view');

        $validated = $request->validated();

        $medications = Medication::query()
            ->when(
                $validated['search'] ?? null,
                fn ($query, $search) =>
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('generic_name', 'like', "%{$search}%")
            )
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Medications/Index', [
            'medications' => $medications,
            'filters' => [
                'search' => $validated['search'] ?? '',
            ],
        ]);
    }

    public function create(): Response
    {
        // $this->authorize('medications.create');

        return Inertia::render('Medications/Create');
    }

    public function store(MedicationRequest $request): RedirectResponse
    {
        // $this->authorize('medications.create');

        $validated = $request->validated();

        Medication::create($validated);

        return redirect()->route('medications.index')->with('message', 'Medication created successfully.');
    }

    public function edit(Medication $medication): Response
    {
        // $this->authorize('medications.view');

        return Inertia::render('Medications/Edit', [
            'medication' => $medication->only(['id', 'name', 'generic_name', 'dosage_form', 'strength']),
        ]);
    }

    public function update(MedicationRequest $request, Medication $medication): RedirectResponse
    {
        // $this->authorize('medications.update');

        $validated = $request->validated();

        $medication->update($validated);

        return redirect()->route('medications.index')->with('message', 'Medication updated successfully.');
    }

    public function destroy(Medication $medication): RedirectResponse
    {
        // $this->authorize('medications.delete');

        $medication->delete();

        return redirect()->route('medications.index')->with('message', 'Medication deleted successfully.');
    }
}