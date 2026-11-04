<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Treatment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

/**
 * ASSUMPTIONS (adjust to your schema):
 * - appointments: patient_id, appointment_date (datetime), status, reason
 *   status values: scheduled | completed | cancelled | no_show
 * - Appointment has a `patient()` relation; patients table has `name`
 * - Treatment has an `appointment()` relation, `follow_up_schedule`,
 *   and optionally `procedure` (null if the column doesn't exist)
 */
class DentistDashboardController extends Controller
{
    public function index(Request $request)
    {
        $dentist = $request->user();
        $now     = Carbon::now();
        $today   = Carbon::today();

        // ---------- Today's schedule ----------
        $todayAppointments = Appointment::with('patient:id,first_name,middle_name,last_name')
            ->whereDate('appointment_date', $today)
            ->orderBy('appointment_date')
            ->get();

        $schedule = $todayAppointments->map(fn ($a) => [
            'id'      => $a->id,
            'time'    => Carbon::parse($a->appointment_date)->format('g:i A'),
            'patient' => $a->patient?->full_name ?? 'Unknown patient',
            'reason'  => $a->reason,
            'status'  => $a->status ?? 'scheduled',
        ])->values();

        // Next patient still to be seen today
        $next = $todayAppointments->first(
            fn ($a) => ($a->status ?? 'scheduled') === 'scheduled'
                && Carbon::parse($a->appointment_date)->gte($now)
        );

        $nextPatient = $next ? [
            'id'      => $next->id,
            'time'    => Carbon::parse($next->appointment_date)->format('g:i A'),
            'patient' => $next->patient?->full_name ?? 'Unknown patient',
            'reason'  => $next->reason,
        ] : null;

        // ---------- Stats ----------
        $totalPatientsTreated = Treatment::query()
            ->where('treatments.user_id', $dentist->id)
            ->join('appointments', 'appointments.id', '=', 'treatments.appointment_id')
            ->distinct()
            ->count('appointments.patient_id');

        $pendingFollowUps = Treatment::where('user_id', $dentist->id)
            ->whereNotNull('follow_up_schedule')
            ->whereDate('follow_up_schedule', '>=', $today)
            ->count();

        $completedToday = $todayAppointments->where('status', 'completed')->count();
        $cancelledToday = $todayAppointments->whereIn('status', ['cancelled', 'no_show'])->count();

        // ---------- This week's load (Mon–Sun) ----------
        $weekStart = $today->copy()->startOfWeek();
        $weekEnd   = $today->copy()->endOfWeek();

        $weekCounts = Appointment::whereBetween('appointment_date', [$weekStart, $weekEnd->copy()->endOfDay()])
            ->get()
            ->groupBy(fn ($a) => Carbon::parse($a->appointment_date)->toDateString())
            ->map->count();

        $weekLoad = collect(range(0, 6))->map(function ($i) use ($weekStart, $weekCounts, $today) {
            $day = $weekStart->copy()->addDays($i);

            return [
                'label'   => $day->format('D'),
                'count'   => $weekCounts[$day->toDateString()] ?? 0,
                'isToday' => $day->isSameDay($today),
            ];
        })->values();

        // ---------- Upcoming follow-ups ----------
        $followUps = Treatment::with('appointment.patient:id,first_name,middle_name,last_name')
            ->where('user_id', $dentist->id)
            ->whereNotNull('follow_up_schedule')
            ->whereDate('follow_up_schedule', '>=', $today)
            ->orderBy('follow_up_schedule')
            ->limit(5)
            ->get()
            ->map(fn ($t) => [
                'id'        => $t->id,
                'patient'   => $t->appointment?->patient?->full_name ?? 'Unknown patient',
                'procedure' => $t->procedure,
                'date'      => Carbon::parse($t->follow_up_schedule)->format('M j'),
                'daysAway'  => $today->diffInDays(Carbon::parse($t->follow_up_schedule)->startOfDay()),
            ])->values();

        // ---------- Recent treatments ----------
        $recentTreatments = Treatment::with('appointment.patient:id,first_name,middle_name,last_name')
            ->where('user_id', $dentist->id)
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($t) => [
                'id'        => $t->id,
                'patient'   => $t->appointment?->patient?->full_name ?? 'Unknown patient',
                'procedure' => $t->procedure,
                'date'      => $t->created_at?->format('M j'),
            ])->values();

        return Inertia::render('Dashboards/DentistDashboard', [
            'dentist' => ['name' => $dentist->name],
            'today'   => $today->format('F j, Y'),
            'stats'   => [
                'todayAppointments'    => $todayAppointments->count(),
                'completedToday'       => $completedToday,
                'cancelledToday'       => $cancelledToday,
                'totalPatientsTreated' => $totalPatientsTreated,
                'pendingFollowUps'     => $pendingFollowUps,
            ],
            'nextPatient'      => $nextPatient,
            'schedule'         => $schedule,
            'weekLoad'         => $weekLoad,
            'followUps'        => $followUps,
            'recentTreatments' => $recentTreatments,
        ]);
    }
}