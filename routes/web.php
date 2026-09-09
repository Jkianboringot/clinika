<?php

use App\Http\Controllers\BranchController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProcedureController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SaleController;
use App\Models\Branch;
use Illuminate\Support\Facades\Route;

Route::redirect('/', 'dashboard')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

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
