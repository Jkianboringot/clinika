<?php

use App\Http\Controllers\Auth\PatientLoginController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Dashboards\PatientDashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\MedicationController;
use App\Http\Controllers\Patient\AppointmentController as PatientAppointmentController;
use App\Http\Controllers\Patient\TreatmentController as PatientTreatmentController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ProcedureController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TreatmentController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\PatientPasswordResetController;

Route::post('patient/forgot-password/code', [PatientPasswordResetController::class, 'sendCode'])
    ->middleware('throttle:3,1')
    ->name('patient.password.code');

Route::post('patient/forgot-password/reset', [PatientPasswordResetController::class, 'reset'])
    ->middleware('throttle:10,1')
    ->name('patient.password.reset');


/*
|--------------------------------------------------------------------------
| ROLE LEGEND (MB Klinika)
|--------------------------------------------------------------------------
| Admin   = dashboard, user management, reports
| Staff   = patients, appointments, treatments, inventory, procedures, medications, reports
| Dentist = view/add/update patients, treatments, view schedule
| Patient = book own appointments, view own treatment summaries  (guard: patient)
|
| Permission naming: <module>.<action>
| Staff/Admin/Dentist -> guard "web"   | Patient -> guard "patient"
*/

// Redirect "/" depending on who is logged in
Route::get('/', function () {
    if (Auth::guard('patient')->check()) {
        return redirect()->route('patient.dashboard');
    }

    return redirect()->route('dashboard');
})->name('home');

/*
|--------------------------------------------------------------------------
| PATIENT AUTH (guest + logout)
|--------------------------------------------------------------------------
*/
Route::get('/patient/login', [PatientLoginController::class, 'create'])->name('patient.login');
Route::post('/patient/login', [PatientLoginController::class, 'store'])->name('patient.login.store');
Route::post('/patient/logout', [PatientLoginController::class, 'destroy'])
    ->middleware('auth:patient')
    ->name('patient.logout');

/*
|--------------------------------------------------------------------------
| PATIENT PORTAL (guard: patient)
|--------------------------------------------------------------------------
| Permission middleware needs the guard as 2nd argument: permission:name,patient
*/
Route::middleware(['auth:patient'])->prefix('patient')->name('patient.')->group(function () {
    Route::get('dashboard', [PatientDashboardController::class, 'index'])
        ->middleware('permission:patient_dashboard.view,patient')->name('dashboard');

    Route::get('appointments', [PatientAppointmentController::class, 'index'])
        ->middleware('permission:appointments.view_own,patient')->name('appointments.index');

    Route::get('treatments', [PatientTreatmentController::class, 'index'])
        ->middleware('permission:treatments.view_own,patient')->name('treatments.index');
});

/*
|--------------------------------------------------------------------------
| STAFF SIDE (guard: web) - admin, staff, dentist
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:web', 'verified'])->group(function () {

    // One /dashboard route: DashboardController picks admin / dentist / staff by permission
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // ===== PROCEDURES =====
    Route::prefix('procedures')->name('procedures.')->group(function () {
        Route::get('/', [ProcedureController::class, 'index'])->middleware('permission:procedures.view')->name('index');
        Route::get('/create', [ProcedureController::class, 'create'])->middleware('permission:procedures.create')->name('create');
        Route::post('/', [ProcedureController::class, 'store'])->middleware('permission:procedures.create')->name('store');
        Route::get('/{procedure}/edit', [ProcedureController::class, 'edit'])->middleware('permission:procedures.update')->name('edit');
        Route::put('/{procedure}', [ProcedureController::class, 'update'])->middleware('permission:procedures.update')->name('update');
        Route::delete('/{procedure}', [ProcedureController::class, 'destroy'])->middleware('permission:procedures.delete')->name('destroy');
    });

    // ===== MEDICATIONS =====
    Route::prefix('medications')->name('medications.')->group(function () {
        Route::get('/', [MedicationController::class, 'index'])->middleware('permission:medications.view')->name('index');
        Route::get('/create', [MedicationController::class, 'create'])->middleware('permission:medications.create')->name('create');
        Route::post('/', [MedicationController::class, 'store'])->middleware('permission:medications.create')->name('store');
        Route::get('/{medication}/edit', [MedicationController::class, 'edit'])->middleware('permission:medications.update')->name('edit');
        Route::put('/{medication}', [MedicationController::class, 'update'])->middleware('permission:medications.update')->name('update');
        Route::delete('/{medication}', [MedicationController::class, 'destroy'])->middleware('permission:medications.delete')->name('destroy');
    });

    // ===== INVENTORY ITEMS ===== (permissions: inventory.*)
    Route::prefix('inventory_items')->name('inventory_items.')->group(function () {
        Route::get('/', [InventoryItemController::class, 'index'])->middleware('permission:inventory.view')->name('index');
        Route::get('/create', [InventoryItemController::class, 'create'])->middleware('permission:inventory.create')->name('create');
        Route::post('/', [InventoryItemController::class, 'store'])->middleware('permission:inventory.create')->name('store');
        Route::get('/{inventoryItem}/edit', [InventoryItemController::class, 'edit'])->middleware('permission:inventory.update')->name('edit');
        Route::put('/{inventoryItem}', [InventoryItemController::class, 'update'])->middleware('permission:inventory.update')->name('update');
        Route::delete('/{inventoryItem}', [InventoryItemController::class, 'destroy'])->middleware('permission:inventory.delete')->name('destroy');
    });

    // ===== INVENTORIES ===== (permissions: inventory.*)
    Route::prefix('inventories')->name('inventories.')->group(function () {
        Route::get('/', [InventoryController::class, 'index'])->middleware('permission:inventory.view')->name('index');
        Route::get('/create', [InventoryController::class, 'create'])->middleware('permission:inventory.create')->name('create');
        Route::post('/', [InventoryController::class, 'store'])->middleware('permission:inventory.create')->name('store');
        Route::get('/{inventory}/edit', [InventoryController::class, 'edit'])->middleware('permission:inventory.update')->name('edit');
        Route::put('/{inventory}', [InventoryController::class, 'update'])->middleware('permission:inventory.update')->name('update');
        Route::delete('/{inventory}', [InventoryController::class, 'destroy'])->middleware('permission:inventory.delete')->name('destroy');
    });

    // ===== PATIENTS =====
    Route::prefix('patients')->name('patients.')->group(function () {
        Route::get('/', [PatientController::class, 'index'])->middleware('permission:patients.view')->name('index');
        Route::get('/create', [PatientController::class, 'create'])->middleware('permission:patients.create')->name('create');
        Route::post('/', [PatientController::class, 'store'])->middleware('permission:patients.create')->name('store');
        Route::get('/{patient}/edit', [PatientController::class, 'edit'])->middleware('permission:patients.update')->name('edit');
        Route::put('/{patient}/deactivate', [PatientController::class, 'deactivate'])->middleware('permission:patients.delete')->name('deactivate');
        Route::put('/{patient}', [PatientController::class, 'update'])->middleware('permission:patients.update')->name('update');
        Route::get('/{patient}', [PatientController::class, 'show'])->middleware('permission:patients.view')->name('show');
        Route::put('/{patient}/activate', [PatientController::class, 'activate'])->name('activate');
    });

    // ===== USERS ===== (admin)
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->middleware('permission:users.view')->name('index');
        Route::get('/create', [UserController::class, 'create'])->middleware('permission:users.create')->name('create');
        Route::post('/', [UserController::class, 'store'])->middleware('permission:users.create')->name('store');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->middleware('permission:users.update')->name('edit');
        Route::put('/{user}/deactivate', [UserController::class, 'deactivate'])->middleware('permission:users.delete')->name('deactivate');
        Route::put('/{user}', [UserController::class, 'update'])->middleware('permission:users.update')->name('update');
        Route::put('/{user}/activate', [UserController::class, 'activate'])->middleware('permission:users.update')->name('activate');
    });

    // ===== APPOINTMENTS =====
    Route::prefix('appointments')->name('appointments.')->group(function () {
        Route::get('/', [AppointmentController::class, 'index'])->middleware('permission:appointments.view')->name('index');
        Route::get('/create', [AppointmentController::class, 'create'])->middleware('permission:appointments.create')->name('create');
        Route::post('/', [AppointmentController::class, 'store'])->middleware('permission:appointments.create')->name('store');
        Route::get('/{appointment}/edit', [AppointmentController::class, 'edit'])->middleware('permission:appointments.update')->name('edit');
        Route::put('/{appointment}', [AppointmentController::class, 'update'])->middleware('permission:appointments.update')->name('update');
        Route::delete('/{appointment}', [AppointmentController::class, 'destroy'])->middleware('permission:appointments.delete')->name('destroy');
    });

    // ===== TREATMENTS =====
    Route::prefix('treatments')->name('treatments.')->group(function () {
        Route::get('/', [TreatmentController::class, 'index'])->middleware('permission:treatments.view')->name('index');
        Route::get('{appointment}/create', [TreatmentController::class, 'create'])->middleware('permission:treatments.create')->name('create');
        Route::post('{appointment}', [TreatmentController::class, 'store'])->middleware('permission:treatments.create')->name('store');
        Route::get('/{treatment}/edit', [TreatmentController::class, 'edit'])->middleware('permission:treatments.update')->name('edit');
        Route::put('/{treatment}', [TreatmentController::class, 'update'])->middleware('permission:treatments.update')->name('update');
        Route::delete('/{treatment}', [TreatmentController::class, 'destroy'])->middleware('permission:treatments.delete')->name('destroy');
    });

    // ===== REPORTS =====
    Route::get('/reports', [ReportController::class, 'index'])->middleware('permission:reports.view')->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->middleware('permission:reports.view')->name('reports.export');
});

require __DIR__ . '/settings.php';