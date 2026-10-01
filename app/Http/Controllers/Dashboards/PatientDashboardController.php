<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Treatment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PatientDashboardController extends Controller
{
    // Appointments with these statuses are not "upcoming". Match your AppointmentStatusEnum values.
    private const INACTIVE_STATUSES = ['cancelled', 'completed'];

    public function index(Request $request)
    {
        $today = Carbon::today();

        // Patients log in on their own guard, so the logged-in user IS the patient record
        $patient = Auth::guard('patient')->user();

        if (! $patient) {
              return Inertia::render('Dashboards/PatientDashboard', [
                'stats'               => ['upcomingAppointments' => 0, 'followUps' => 0],
                'upcomingAppointment' => null,
            ]);
        }

        $upcoming = Appointment::with(['procedure', 'staff'])
            ->where('patient_id', $patient->id)
            ->whereDate('appointment_date', '>=', $today)
            ->whereNotIn('status', self::INACTIVE_STATUSES)
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->get();

        $followUps = Treatment::whereHas('appointment', fn ($q) => $q->where('patient_id', $patient->id))
            ->whereNotNull('follow_up_schedule')
            ->whereDate('follow_up_schedule', '>=', $today)
            ->count();

        $next = $upcoming->first();

            return Inertia::render('Dashboards/PatientDashboard', [
            'stats' => [
                'upcomingAppointments' => $upcoming->count(),
                'followUps'            => $followUps,
            ],
            'upcomingAppointment' => $next ? [
                'id'        => $next->id,
                'procedure' => $next->procedure->name ?? '—',
                'dentist'   => $next->staff->name ?? '—',
                'date'      => Carbon::parse($next->appointment_date)->format('F j, Y'),
                'time'      => Carbon::parse($next->appointment_time)->format('g:i A'),
                'status'    => (string) $next->status,
            ] : null,
        ]);
    }
}