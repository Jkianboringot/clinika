<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Http\Requests\PatientRequest;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;


class PatientController extends Controller
{
    public function index(Request $request): Response
    {
        // TODO: add this later
        // $this->authorize('patients.view');


        $patients = Patient::query()
            ->when(
                $request->input('search'),
                fn($query, $search) =>
                    $query->where('full_name', 'like', "%{$search}%")
            )
            ->orderBy('created_at')
            ->paginate(15)
            ->withQueryString();
                // dd($patients);
        return Inertia::render('Patients/Index', [
            'patients' => $patients,
            'filters' => [
                'search' => $request->input('search'),
            ],
        ]);
    }

    public function create(): Response
    {
        // $this->authorize('patients.create');

        return Inertia::render('Patients/Create');
    }

    public function store(PatientRequest $request): RedirectResponse
    {
        // $this->authorize('patients.create');


        Patient::create($request->validated());

        return redirect()->route('patients.index')->with('message', 'Patient created successfully.');
    }

    public function edit(Patient $patient): Response
    {
        // $this->authorize('patients.view');

        return Inertia::render('Patients/Edit', [
            'patient' => $patient,
        ]);
    }

    public function update(PatientRequest $request, Patient $patient): RedirectResponse
    {
        $data = $request->validated();

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = bcrypt($data['password']);
        }

        $patient->update($data);

        return redirect()->route('patients.index')->with('message', 'Patient updated successfully.');
    }



 public function destroy(Patient $patient): RedirectResponse
    {

        if ($patient->appointments()->exists()) {
            return redirect()
                ->route('patients.index')
                ->with('error', 'This patient has appointment assigned and cannot be deleted.');
        }
        try {
            $patient->deleteOrFail();

        } catch (\Throwable $e) {
            return redirect()->route('patients.index')->with('error', 'Failed to delete, already has with treatment');
        }

        return redirect()->route('patients.index')->with('message', 'Patients deleted successfully.');
    }

    


    public function show(Patient $patient): Response
    {
        // $this->authorize('patients.view');

        return Inertia::render('Patients/Show', [
            'patient' => $patient,
        ]);
    }
}