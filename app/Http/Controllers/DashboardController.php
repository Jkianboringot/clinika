<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Patient;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();

        $totalUsers = User::count();
        $totalUsersLastMonth = User::where('created_at', '<', $now->copy()->startOfMonth())->count();
        $userGrowth = $totalUsersLastMonth > 0
            ? round((($totalUsers - $totalUsersLastMonth) / $totalUsersLastMonth) * 100, 1)
            : 0;

        $patientsCount = Patient::count();
        $dentistsCount = User::role('dentist')->count();
        $staffCount = User::role('staff')->count();
        $adminCount = User::role('admin')->count();

        // User signups over the last 5 months
        $months = collect(range(4, 0))->map(fn ($i) => $now->copy()->subMonths($i));

        $userTrends = $months->map(fn ($m) => [
            'month' => $m->format('M'),
            'users' => User::whereMonth('created_at', $m->month)
                ->whereYear('created_at', $m->year)
                ->count(),
        ])->values();

        return Inertia::render('dashboard', [
            'stats' => [
                'totalUsers' => $totalUsers,
                'userGrowth' => $userGrowth,
            ],
            'userDistribution' => [
                'patients' => $patientsCount,
                'dentists' => $dentistsCount,
                'staff' => $staffCount,
                'admins' => $adminCount,
            ],
            'systemStats' => [
                'activePatients' => $patientsCount,
                'dentists' => $dentistsCount,
                'staffMembers' => $staffCount,
                'admins' => $adminCount,
                'totalUsers' => $totalUsers,
            ],
            'userTrends' => $userTrends,
        ]);
    }
}