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
    public function index(Request $request)
    {
        $dentist = $request->user();
        $today   = Carbon::today();

        // All appointments today (not filtered by user)
        $todayAppointments = Appointment::whereDate('appointment_date', $today)->count();

        // Distinct patients treated by this dentist, all time
        $totalPatientsTreated = Treatment::query()
            ->where('treatments.user_id', $dentist->id)
            ->join('appointments', 'appointments.id', '=', 'treatments.appointment_id')
            ->distinct()
            ->count('appointments.patient_id');

        $pendingFollowUps = Treatment::where('user_id', $dentist->id)
            ->whereNotNull('follow_up_schedule')
            ->whereDate('follow_up_schedule', '>=', $today)
            ->count();

        return Inertia::render('Dashboards/DentistDashboard', [
            'dentist' => ['name' => $dentist->name],
            'today'   => $today->format('F j, Y'),
            'stats'   => [
                'todayAppointments'    => $todayAppointments,
                'totalPatientsTreated' => $totalPatientsTreated,
                'pendingFollowUps'     => $pendingFollowUps,
            ],
        ]);
    }
}