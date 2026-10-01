<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Dashboards\AdminDashboardController;
use App\Http\Controllers\Dashboards\DentistDashboardController;
use App\Http\Controllers\Dashboards\StaffDashboardController;
use Illuminate\Http\Request;

/**
 * Single /dashboard entry point for the web guard.
 * Picks the right dashboard from the user's permission.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        $controller = match (true) {
            $user->can('admin_dashboard.view')   => AdminDashboardController::class,
            $user->can('dentist_dashboard.view') => DentistDashboardController::class,
            $user->can('staff_dashboard.view')   => StaffDashboardController::class,
            default                              => abort(403),
        };

       return app()->call([app($controller), 'index']);
    }
}