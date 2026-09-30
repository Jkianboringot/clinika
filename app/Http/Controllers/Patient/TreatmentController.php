<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Treatment;
use Inertia\Inertia;

class TreatmentController extends Controller
{
    public function index()
    {
        $treatments = Treatment::whereHas('appointment', function ($query) {
            $query->where('patient_id', auth('patient')->id());
        })
            // user = dentist (treatments.user_id). Select only id+name so the
            // patient never receives the dentist's email or other fields.
            ->with([
                'doctor:id,name',
                'appointment:id,procedure_id,appointment_date,appointment_time',
                'appointment.procedure:id,name',
            ])
            ->latest('id')
            ->paginate(10);

        return Inertia::render('Patients/Treatment/Index', [
            'treatments' => $treatments,
        ]);
    }
}