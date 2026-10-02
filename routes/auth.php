<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\VerifyEmailController;

// All auth routes are handled by the Filament admin panel. The targets are the
// real v4 panel URLs (see `php artisan route:list --path=admin`): v3 used
// `/admin/password/reset`, which doesn't exist in v4. The panel has no
// registration page, so `register` goes to the panel login. Password-reset
// links in emails are built by Filament's own ResetPassword notification and
// point at `/admin/password-reset/reset`, not at `password.reset` below.
Route::middleware('guest')->group(function () {
    Route::redirect('login', '/admin/login')->name('login');
    Route::redirect('register', '/admin/login')->name('register');
    Route::redirect('forgot-password', '/admin/password-reset/request')->name('password.request');
    Route::redirect('reset-password/{token}', '/admin/password-reset/request')->name('password.reset');
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
