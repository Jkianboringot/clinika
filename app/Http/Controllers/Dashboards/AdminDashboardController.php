<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\InventoryItem;
use App\Models\Patient;
use App\Models\User;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboards/AdminDashboard', [
            'stats' => [
                'totalUsers'        => User::count(),
                'totalAppointments' => Appointment::count(),
                'totalInventory'    => InventoryItem::count(),
            ],
            'userDistribution' => [
                'patients' => Patient::count(),
                'dentists' => User::role('dentist')->count(),
                'staff'    => User::role('staff')->count(),
            ],
        ]);
    }
}