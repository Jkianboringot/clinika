<?php

use App\Http\Controllers\BranchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\MedicationController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ProcedureController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SaleController;
use App\Models\Branch;
use Illuminate\Support\Facades\Route;

Route::redirect('/', 'dashboard')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('procedures')->name('procedures.')->group(
        function () {
            Route::get('/', [ProcedureController::class, 'index'])->name('index');
            Route::get('/create', [ProcedureController::class, 'create'])->name('create');
            Route::post('/', [ProcedureController::class, 'store'])->name('store');
            Route::get('/{procedure}/edit', [ProcedureController::class, 'edit'])->name('edit');

            Route::delete('/{procedure}', [ProcedureController::class, 'destroy'])->name('destroy');
            Route::put('/{procedure}', [ProcedureController::class, 'update'])->name('update');
        }
    );

    Route::prefix('medications')->name('medications.')->group(
        function () {
            Route::get('/', [MedicationController::class, 'index'])->name('index');
            Route::get('/create', [MedicationController::class, 'create'])->name('create');
            Route::post('/', [MedicationController::class, 'store'])->name('store');
            Route::get('/{medication}/edit', [MedicationController::class, 'edit'])->name('edit');

            Route::delete('/{medication}', [MedicationController::class, 'destroy'])->name('destroy');
            Route::put('/{medication}', [MedicationController::class, 'update'])->name('update');
        }
    );

    Route::prefix('inventory_items')->name('inventory_items.')->group(
        function () {
            Route::get('/', [InventoryItemController::class, 'index'])->name('index');
            Route::get('/create', [InventoryItemController::class, 'create'])->name('create');
            Route::post('/', [InventoryItemController::class, 'store'])->name('store');
            Route::get('/{inventoryItem}/edit', [InventoryItemController::class, 'edit'])->name('edit');

            Route::delete('/{inventoryItem}', [InventoryItemController::class, 'destroy'])->name('destroy');
            Route::put('/{inventoryItem}', [InventoryItemController::class, 'update'])->name('update');
        }
    );

    Route::prefix('patients')->name('patients.')->group(
        function () {
            Route::get('/', [PatientController::class, 'index'])->name('index');
            Route::get('/create', [PatientController::class, 'create'])->name('create');
            Route::post('/', [PatientController::class, 'store'])->name('store');
            Route::get('/{patient}/edit', [PatientController::class, 'edit'])->name('edit');

            Route::delete('/{patient}', [PatientController::class, 'destroy'])->name('destroy');
            Route::put('/{patient}', [InventoryItemController::class, 'update'])->name('update');
        }
    );
    Route::middleware('permission:users.view')->prefix('users')->name('users.')->group(
        function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::get('/create', [UserController::class, 'create'])->name('create');
            Route::post('/', [UserController::class, 'store'])->name('store');
            Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');

            Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
            Route::put('/{user}', [UserController::class, 'update'])->name('update');
        }
    );









});

require __DIR__ . '/settings.php';
