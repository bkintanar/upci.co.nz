<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\VerifyEmailController;

// All auth routes are handled by Filament admin panel
Route::middleware('guest')->group(function () {
    Route::redirect('login', '/admin/login')->name('login');
    Route::redirect('register', '/admin/register')->name('register');
    Route::redirect('forgot-password', '/admin/password/reset')->name('password.request');
    Route::redirect('reset-password/{token}', '/admin/password/reset')->name('password.reset');
});

Route::middleware('auth')->group(function () {
    Route::redirect('verify-email', '/admin')->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::redirect('confirm-password', '/admin')->name('password.confirm');
});

Route::post('logout', App\Livewire\Actions\Logout::class)
    ->name('logout');
