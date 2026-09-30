    <?php

    use App\Http\Controllers\AppointmentController;
    use App\Http\Controllers\BranchController;
    use App\Http\Controllers\DashboardController;
    use App\Http\Controllers\InventoryController;
    use App\Http\Controllers\InventoryItemController;
    use App\Http\Controllers\MedicationController;
    use App\Http\Controllers\PatientController;
    use App\Http\Controllers\ProcedureController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
    use App\Http\Controllers\SaleController;
    use App\Http\Controllers\TreatmentController;
    use App\Models\Branch;
    use Illuminate\Support\Facades\Route;

    /*
    |--------------------------------------------------------------------------
    | ROLE LEGEND (MB Klinika – Table 4 / Use Case Diagram)
    |--------------------------------------------------------------------------
    | Admin   = dashboard, user management, reports, activity logs
    | Staff   = patients, appointments, treatments, inventory, reports
    | Dentist = view/add/update patients, review treatment history, view schedule
    | Patient = book own appointments, view own treatment summaries
    |
    | Permission naming: <module>.<action>  (same style as the existing users.view)
    | Items marked (assumed) are not defined in the capstone document.
    */

    // Roles: All authenticated users | Permission: none (redirect only)
    Route::redirect('/', 'dashboard')->name('home');

    Route::middleware(['auth', 'verified'])->group(function () {
        // Roles: Admin | Permission: dashboard.view
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // ===== PROCEDURES ===== Users: Staff (manage), Dentist (view) (assumed) | Permissions: procedures.*
        Route::prefix('procedures')->name('procedures.')->group(
            function () {
                // Roles: Staff, Dentist | Permission: procedures.view
                Route::get('/', [ProcedureController::class, 'index'])->name('index');
                // Roles: Staff | Permission: procedures.create
                Route::get('/create', [ProcedureController::class, 'create'])->name('create');
                // Roles: Staff | Permission: procedures.create
                Route::post('/', [ProcedureController::class, 'store'])->name('store');
                // Roles: Staff | Permission: procedures.update
                Route::get('/{procedure}/edit', [ProcedureController::class, 'edit'])->name('edit');

                // Roles: Staff | Permission: procedures.delete
                Route::delete('/{procedure}', [ProcedureController::class, 'destroy'])->name('destroy');
                // Roles: Staff | Permission: procedures.update
                Route::put('/{procedure}', [ProcedureController::class, 'update'])->name('update');
            }
        );

        // ===== MEDICATIONS ===== Users: Staff (manage), Dentist (view) (assumed) | Permissions: medications.*
        Route::prefix('medications')->name('medications.')->group(
            function () {
                // Roles: Staff, Dentist | Permission: medications.view
                Route::get('/', [MedicationController::class, 'index'])->name('index');
                // Roles: Staff | Permission: medications.create
                Route::get('/create', [MedicationController::class, 'create'])->name('create');
                // Roles: Staff | Permission: medications.create
                Route::post('/', [MedicationController::class, 'store'])->name('store');
                // Roles: Staff | Permission: medications.update
                Route::get('/{medication}/edit', [MedicationController::class, 'edit'])->name('edit');

                // Roles: Staff | Permission: medications.delete
                Route::delete('/{medication}', [MedicationController::class, 'destroy'])->name('destroy');
                // Roles: Staff | Permission: medications.update
                Route::put('/{medication}', [MedicationController::class, 'update'])->name('update');
            }
        );

        // ===== INVENTORY ITEMS ===== Users: Staff (Inventory System module) | Permissions: inventory_items.*
        Route::prefix('inventory_items')->name('inventory_items.')->group(
            function () {
                // Roles: Staff | Permission: inventory_items.view
                Route::get('/', [InventoryItemController::class, 'index'])->name('index');
                // Roles: Staff | Permission: inventory_items.create
                Route::get('/create', [InventoryItemController::class, 'create'])->name('create');
                // Roles: Staff | Permission: inventory_items.create
                Route::post('/', [InventoryItemController::class, 'store'])->name('store');
                // Roles: Staff | Permission: inventory_items.update
                Route::get('/{inventoryItem}/edit', [InventoryItemController::class, 'edit'])->name('edit');

                // Roles: Staff | Permission: inventory_items.delete
                Route::delete('/{inventoryItem}', [InventoryItemController::class, 'destroy'])->name('destroy');
                // Roles: Staff | Permission: inventory_items.update
                Route::put('/{inventoryItem}', [InventoryItemController::class, 'update'])->name('update');
            }
        );

        // ===== PATIENTS ===== Users: Staff, Dentist (Patient Management module) | Permissions: patients.*
        Route::prefix('patients')->name('patients.')->group(
            function () {
                // Roles: Staff, Dentist | Permission: patients.view
                Route::get('/', [PatientController::class, 'index'])->name('index');
                // Roles: Staff, Dentist | Permission: patients.create
                Route::get('/create', [PatientController::class, 'create'])->name('create');
                // Roles: Staff, Dentist | Permission: patients.create
                Route::post('/', [PatientController::class, 'store'])->name('store');
                // Roles: Staff, Dentist | Permission: patients.update
                Route::get('/{patient}/edit', [PatientController::class, 'edit'])->name('edit');

                // Roles: Staff | Permission: patients.deactivate
        Route::put('/{patient}/deactivate', [PatientController::class, 'deactivate'])->name('deactivate');
                // Roles: Staff, Dentist | Permission: patients.update
                Route::put('/{patient}', [PatientController::class, 'update'])->name('update');
                // Roles: Staff, Dentist | Permission: patients.view
                Route::get('/{patient}', [PatientController::class, 'show'])->name('show');
            }
        );
        // ===== USERS ===== Users: Admin only (user management) | Permissions: users.*
        Route::middleware('permission:users.view')->prefix('users')->name('users.')->group(
            function () {
                // Roles: Admin | Permission: users.view
                Route::get('/', [UserController::class, 'index'])->name('index');
                // Roles: Admin | Permission: users.create
                Route::get('/create', [UserController::class, 'create'])->name('create');
                // Roles: Admin | Permission: users.create
                Route::post('/', [UserController::class, 'store'])->name('store');
                // Roles: Admin | Permission: users.update
                Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');

                // Roles: Admin | Permission: users.deactivate
            Route::put('/{user}/deactivate', [UserController::class, 'deactivate'])->name('deactivate');
                // Roles: Admin | Permission: users.update
                Route::put('/{user}', [UserController::class, 'update'])->name('update');
            }
        );

        // ===== APPOINTMENTS ===== Users: Staff (manage), Patient (book own), Dentist (view schedule) | Permissions: appointments.*
        Route::prefix('appointments')->name('appointments.')->group(
            function () {
                // Roles: Staff, Dentist, Patient (own only) | Permission: appointments.view
                Route::get('/', [AppointmentController::class, 'index'])->name('index');
                // Roles: Staff, Patient | Permission: appointments.create
                Route::get('/create', [AppointmentController::class, 'create'])->name('create');
                // Roles: Staff, Patient | Permission: appointments.create
                Route::post('/', [AppointmentController::class, 'store'])->name('store');
                // Roles: Staff | Permission: appointments.update
                Route::get('/{appointment}/edit', [AppointmentController::class, 'edit'])->name('edit');

                // Roles: Staff | Permission: appointments.delete
                Route::delete('/{appointment}', [AppointmentController::class, 'destroy'])->name('destroy');
                // Roles: Staff | Permission: appointments.update
                Route::put('/{appointment}', [AppointmentController::class, 'update'])->name('update');
            }
        );

        // ===== TREATMENTS ===== Users: Staff (record), Dentist (review history) | Permissions: treatments.*
        Route::prefix('treatments')->name('treatments.')->group(
            function () {
                // Roles: Staff, Dentist | Permission: treatments.view
                Route::get('/', [TreatmentController::class, 'index'])->name('index');
                // Roles: Staff | Permission: treatments.create
                Route::get('{appointment}/create', [TreatmentController::class, 'create'])->name('create');
                // Roles: Staff | Permission: treatments.create (also triggers post-treatment email + inventory deduction)
                Route::post('{appointment}/', [TreatmentController::class, 'store'])->name('store');
                // Roles: Staff | Permission: treatments.update
                Route::get('/{treatment}/edit', [TreatmentController::class, 'edit'])->name('edit');

                // Roles: Staff | Permission: treatments.delete
                Route::delete('/{treatment}', [TreatmentController::class, 'destroy'])->name('destroy');
                // Roles: Staff | Permission: treatments.update
                Route::put('/{treatment}', [TreatmentController::class, 'update'])->name('update');
            }
        );


        // ===== INVENTORIES ===== Users: Staff (Stock Monitoring + low-stock alerts) | Permissions: inventories.*
        Route::prefix('inventories')->name('inventories.')->group(
            function () {
                // Roles: Staff | Permission: inventories.view
                Route::get('/', [InventoryController::class, 'index'])->name('index');
                // Roles: Staff | Permission: inventories.create
                Route::get('/create', [InventoryController::class, 'create'])->name('create');
                // Roles: Staff | Permission: inventories.create
                Route::post('/', [InventoryController::class, 'store'])->name('store');
                // Roles: Staff | Permission: inventories.update
                Route::get('/{inventory}/edit', [InventoryController::class, 'edit'])->name('edit');

                // Roles: Staff | Permission: inventories.delete
                Route::delete('/{inventory}', [InventoryController::class, 'destroy'])->name('destroy');
                // Roles: Staff | Permission: inventories.update
                Route::put('/{inventory}', [InventoryController::class, 'update'])->name('update');
            }
        );


// routes/web.php

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');



    });

    require __DIR__ . '/settings.php';