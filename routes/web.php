<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
})->name('home');

Route::redirect('dashboard', '/admin')->name('dashboard');

Route::middleware(['auth'])->group(function () {
    // Settings routes - redirect to Filament admin panel
    Route::redirect('settings', '/admin')->name('settings');
    Route::redirect('settings/profile', '/admin')->name('settings.profile');
    Route::redirect('settings/password', '/admin')->name('settings.password');
    Route::redirect('settings/appearance', '/admin')->name('settings.appearance');

    // Attendance routes - redirect to Filament admin panel
    Route::redirect('attendance', 'admin/attendances')->name('attendance.index');
    Route::redirect('attendance/create', 'admin/attendances/create')->name('attendance.create');
});

require __DIR__.'/auth.php';
