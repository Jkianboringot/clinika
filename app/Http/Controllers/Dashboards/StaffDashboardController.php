<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\InventoryItem;
use App\Models\Patient;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class StaffDashboardController extends Controller{
    public function index()
    {
        $today = Carbon::today();

        $todayAppointments = Appointment::whereDate('appointment_date', $today)->count();
        $totalPatients     = Patient::count();

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

        return Inertia::render('Dashboards/StaffDashboard', [
            'stats' => [
                'todayAppointments' => $todayAppointments,
                'totalPatients'     => $totalPatients,
                'lowStockItems'     => $lowStock->count(),
                'inventoryItems'    => $items->count(),
            ],
            'lowStock' => $lowStock->take(5)->all(),
        ]);
    }
}