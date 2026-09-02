<?php

use App\Livewire\Cashier\CashierView;
use App\Livewire\Cashier\CurrentStock;
use App\Livewire\Cashier\RecievingStock;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('dashboard');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    // Route::get('/dashboard', fn () => redirect('admin'))->name('dashboard');

    Route::prefix('cashier')->group(function () {
        Route::get('/', CashierView::class)->name('cashier-view');
        Route::get('receiving_stock', RecievingStock::class)->name('receiving_stock');
        Route::get('current_stock', CurrentStock::class)->name('current_stock');

    });

    // i will be using this so that i can seperate the admin panel and it having full filament suport no issues
    // and i can use web as a route for the cashier side for livewire blade
    // probable will create a role check but that sould be easy because i can make it the be the same as prev system
    // also i only really have 2 role admin and cashier adn manager but that came later

    // i might not even need to check role, i can just have a button that show if they want to login as admin, manager, or cashiers
    // and check it here adn base on it have login for that, its like portal for catsu

    Route::fallback(function () {
        return view('welcome');
    });
});
