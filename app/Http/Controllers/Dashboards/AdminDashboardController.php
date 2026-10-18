<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\InventoryItem;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();
        $lastMonth = $now->copy()->subMonthNoOverflow();

        // Remaining stock = all added - all used (same logic as the inventory report)
        $items = InventoryItem::query()
            ->withSum('inventoryStock as all_added', 'quantity')
            ->withSum('treatmentInventories as all_used', 'quantity')
            ->get();

        $lowStock = $items
            ->map(function ($i) {
                $remaining = max(0, ($i->all_added ?? 0) - ($i->all_used ?? 0));

                return [
                    'id'      => $i->id,
                    'name'    => $i->item_name,
                    'current' => (float) $remaining,
                    'minimum' => $i->minimum_stock !== null ? (float) $i->minimum_stock : null,
                ];
            })
            ->filter(fn ($i) => $i['minimum'] !== null && $i['current'] <= $i['minimum'])
            ->map(fn ($i) => $i + [
                'percent'  => $i['minimum'] > 0 ? min(100, round($i['current'] / $i['minimum'] * 100)) : 0,
                'critical' => $i['current'] <= $i['minimum'] * 0.5,
            ])
            ->sortBy('percent')
            ->values();

        return Inertia::render('Dashboards/AdminDashboard', [
            'admin' => ['name' => auth()->user()->name],
            'today' => $now->format('l, F j, Y'),

            'stats' => [
                'totalUsers'            => User::count(),
                'totalPatients'         => Patient::count(),
                'totalAppointments'     => Appointment::count(),
                'totalInventory'        => $items->count(),
                'todayAppointments'     => Appointment::whereDate('appointment_date', $now->toDateString())->count(),
                'pendingAppointments'   => Appointment::where('status', 'pending')->count(),
                'lowStockCount'         => $lowStock->count(),
                'newPatientsThisMonth'  => Patient::whereYear('created_at', $now->year)
                                                  ->whereMonth('created_at', $now->month)
                                                  ->count(),
                'appointmentsThisMonth' => Appointment::whereYear('appointment_date', $now->year)
                                                      ->whereMonth('appointment_date', $now->month)
                                                      ->count(),
                'appointmentsLastMonth' => Appointment::whereYear('appointment_date', $lastMonth->year)
                                                      ->whereMonth('appointment_date', $lastMonth->month)
                                                      ->count(),
            ],

            'userDistribution' => [
                'patients' => Patient::count(),
                'dentists' => User::role('dentist')->count(),
                'staff'    => User::role('staff')->count(),
            ],

            'appointmentTrend'     => $this->lastSevenDays($now),
            'appointmentsByStatus' => $this->byStatus(),
            'recentAppointments'   => $this->recentAppointments(),
            'lowStockItems'        => $lowStock->take(5)->all(),
        ]);
    }

    private function lastSevenDays(Carbon $now): array
    {
        $start = $now->copy()->subDays(6)->startOfDay();

        $counts = Appointment::whereDate('appointment_date', '>=', $start->toDateString())
            ->whereDate('appointment_date', '<=', $now->toDateString())
            ->pluck('appointment_date')
            ->countBy(fn ($d) => Carbon::parse($d)->toDateString());

        return collect(range(0, 6))->map(function ($i) use ($start, $counts) {
            $day = $start->copy()->addDays($i);

            return [
                'label' => $day->format('D, M j'),
                'total' => (int) ($counts[$day->toDateString()] ?? 0),
            ];
        })->all();
    }

    private function byStatus(): array
    {
        return Appointment::select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->get()
            ->map(fn ($row) => [
                'status' => (string) $row->status,
                'total'  => (int) $row->total,
            ])
            ->all();
    }

    private function recentAppointments(): array
    {
        // Assumes Appointment has patient(), staff() and procedure() relationships
        return Appointment::with(['patient', 'staff', 'procedure'])
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn ($a) => [
                'id'        => $a->id,
                'patient'   => $this->displayName($a->patient),
                'staff'     => $this->displayName($a->staff),
                'procedure' => $a->procedure?->name ?? $a->procedure?->procedure_name ?? '—',
                'date'      => Carbon::parse($a->appointment_date)->format('M j, Y'),
                'time'      => $a->appointment_time
                    ? Carbon::parse($a->appointment_time)->format('g:i A')
                    : '—',
                'status'    => (string) $a->status,
            ])
            ->all();
    }

    private function displayName($model): string
    {
        if (! $model) {
            return '—';
        }

        return $model->name
            ?? trim(($model->first_name ?? '') . ' ' . ($model->last_name ?? ''))
            ?: '—';
    }
}