<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Procedure;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AppointmentController extends Controller
{
    public function index()
    {
        $appointments = Appointment::where('patient_id', auth('patient')->id())
            ->with('procedure:id,name')
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time')
            ->paginate(10);

        return Inertia::render('Patients/Appointment/Index', [
            'appointments' => $appointments,
        ]);
    }

    public function create()
    {
        return Inertia::render('patient/appointments/create', [
            'procedures' => Procedure::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'procedure_id' => ['required', 'exists:procedures,id'],
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_time' => ['required'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        // Never take patient_id (or status) from the request
        $data['patient_id'] = auth('patient')->id();
        $data['status'] = 'pending'; // change to the value your status uses for new bookings

        Appointment::create($data);

        return redirect()
            ->route('patient.appointments.index')
            ->with('success', 'Appointment booked.');
    }
}