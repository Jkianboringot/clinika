<?php

use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\PatientPasswordResetController;

Route::middleware(['auth:web,patient'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});


Route::middleware('guest:patient')->group(function () {
    Route::post('patient/forgot-password/code', [PatientPasswordResetController::class, 'sendCode'])
        ->middleware('throttle:3,1')
        ->name('patient.password.code');

    Route::post('patient/forgot-password/reset', [PatientPasswordResetController::class, 'reset'])
        ->middleware('throttle:10,1')
        ->name('patient.password.reset');
});
Route::middleware(['auth:web,patient', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::post('settings/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
    Route::delete('settings/profile/avatar', [ProfileController::class, 'destroyAvatar'])->name('profile.avatar.destroy');
    // Route::inertia('settings/appearance', 'settings/appearance')->name('appearance.edit');

    Route::post('settings/security/password-code', [SecurityController::class, 'sendResetCode'])
        ->middleware('throttle:3,1')
        ->name('security.password-code');

    Route::post('settings/security/password-reset', [SecurityController::class, 'resetPassword'])
        ->middleware('throttle:10,1')
        ->name('security.password-reset');
});
