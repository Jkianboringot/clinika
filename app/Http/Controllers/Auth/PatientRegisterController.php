<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\PatientRegisterRequest;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class PatientRegisterController extends Controller
{
    public function create(): Response|RedirectResponse
    {
        // Already signed in as a patient? No need to register.
        if (Auth::guard('patient')->check()) {
            return redirect()->route('patient.dashboard');
        }

        return Inertia::render('auth/PatientRegister');
    }

    public function store(PatientRegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // The patient chooses their own password (unlike staff-created patients,
        // whose default password is their contact number).
        $data['password'] = Hash::make($data['password']);

        // is_active is intentionally not set here: the column defaults to ACTIVE,
        // and we never want it to come from user input.
        try {
            $patient = Patient::create($data);

            // If patients get their role/permissions through Spatie, assign it here
            // (needed for the permission:...,patient route middleware), unless a
            // model event or observer already does it:
            // $patient->assignRole('patient');
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->with('error', 'Failed to create your account. Please try again.');
        }

        Auth::guard('patient')->login($patient);
        $request->session()->regenerate();

        return redirect()
            ->route('patient.appointments.index')
            ->with('message', 'Welcome to Klinika! Your account has been created.');
    }
}