<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Treatment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class DentistDashboardController extends Controller
{
    // Adjust these to match your AppointmentStatusEnum values
    private const STATUS_IN_PROGRESS = 'in_progress';

    public function index(Request $request)
    {
        $dentist = $request->user();
        $today   = Carbon::today();

        // Today's appointments for this dentist (appointments.staff_id = dentist user id)
        $todayAppointments = Appointment::with(['patient', 'procedure'])
            ->where('staff_id', $dentist->id)
            ->whereDate('appointment_date', $today)
            ->orderBy('appointment_time')
            ->get();

        // Treatments done by this dentist (treatments.doctor_id = dentist user id)
        $treatments = fn () => Treatment::query()
            ->where('treatments.user_id', $dentist->id)
            ->join('appointments', 'appointments.id', '=', 'treatments.appointment_id');

        $patientsTreatedToday = $treatments()
            ->whereDate('appointments.appointment_date', $today)
            ->distinct()
            ->count('appointments.patient_id');

        $totalPatientsTreated = $treatments()
            ->distinct()
            ->count('appointments.patient_id');

        $pendingFollowUps = Treatment::where('user_id', $dentist->id)
            ->whereNotNull('follow_up_schedule')
            ->whereDate('follow_up_schedule', '>=', $today)
            ->count();

        $schedule = $todayAppointments->map(fn ($a) => [
            'id'      => $a->id,
            'time'    => Carbon::parse($a->appointment_time)->format('h:i A'),
            'patient' => $a->patient->full_name,
            'service' => $a->procedure->name ?? '—',
            'status'  => (string) $a->status,
        ])->values();

        $current = $todayAppointments->first(
            fn ($a) => (string) $a->status === self::STATUS_IN_PROGRESS
        );

        return Inertia::render('Dashboards/DentistDashboard', [
            'dentist' => ['name' => $dentist->name],
            'today'   => $today->format('F j, Y'),
            'stats'   => [
                'todayAppointments'    => $todayAppointments->count(),
                'patientsTreatedToday' => $patientsTreatedToday,
                'totalPatientsTreated' => $totalPatientsTreated,
                'pendingFollowUps'     => $pendingFollowUps,
            ],
            'schedule'       => $schedule,
            'currentPatient' => $current ? [
                'name'    => $current->patient->full_name,
                'age'     => Carbon::parse($current->patient->date_of_birth)->age,
                'gender'  => $current->patient->gender,
                'service' => $current->procedure->name ?? '—',
                'time'    => Carbon::parse($current->appointment_time)->format('h:i A'),
                'status'  => (string) $current->status,
            ] : null,
        ]);
    }
}