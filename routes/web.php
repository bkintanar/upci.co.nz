<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ChurchController;

// API routes (must come before catch-all route)
Route::prefix('api')->group(function () {
    // RESTful church routes
    Route::apiResource('churches', ChurchController::class);

    // Additional church-related endpoints
    Route::get('/churches-regions', [ChurchController::class, 'regions']);
    Route::get('/churches-service-days', [ChurchController::class, 'serviceDays']);
    Route::post('/address-search', [ChurchController::class, 'addressSearch']);
});

// Frontend routes
Route::get('/', function () {
    return view('app');
})->name('home');

// Catch-all route for Vue.js SPA (must be last)
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api).*');

// Admin routes (protected)
Route::prefix('admin')->middleware(['auth'])->group(function () {
    // Admin routes will be handled by Filament
});

require __DIR__.'/auth.php';
