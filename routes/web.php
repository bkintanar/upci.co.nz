<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\ChurchController;

// API routes (must come before catch-all route)
Route::prefix('api')->group(function () {
    // RESTful church routes
    Route::apiResource('churches', ChurchController::class);

    // Additional church-related endpoints
    Route::get('/churches-regions', [ChurchController::class, 'regions']);
    Route::get('/churches-service-days', [ChurchController::class, 'serviceDays']);
    Route::post('/address-search', [ChurchController::class, 'addressSearch']);

    // CMS page routes
    Route::get('/pages', [PageController::class, 'index']);
    Route::get('/pages/{slug}', [PageController::class, 'show']);
});

// Frontend routes
Route::get('/', function () {
    return view('app');
})->name('home');

// CMS pages route - accessible at /cms/*
Route::get('/cms/{slug}', function () {
    return view('app');
})->name('cms.page');

// Catch-all route for Vue.js SPA (must be last)
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');

// Admin routes (protected)
Route::prefix('admin')->middleware(['auth'])->group(function () {
    // Admin routes will be handled by Filament
});

require __DIR__.'/auth.php';
