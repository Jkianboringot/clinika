<?php

namespace App\Http\Controllers\Auth;

use App\Enums\IsActiveEnum;
use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PatientLoginController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('auth/PatientLogin');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $patient = Patient::where('email', $credentials['email'])
            ->where('is_active', IsActiveEnum::ACTIVE->value)
            ->first();
        if (
            !$patient ||
            !password_verify($credentials['password'], $patient->password)
        ) {
            throw ValidationException::withMessages([
                'email' => 'The provided credentials are incorrect.',
            ]);
        }

     Auth::guard('patient')->login($patient, $request->boolean('remember'));

$request->session()->regenerate();
        // dd('dd');
return redirect()->route('patient.appointments.index');

    }

    public function destroy(Request $request)
    {
        Auth::guard('patient')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/patient/login');
    }
}