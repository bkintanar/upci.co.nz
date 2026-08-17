<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\ChurchController;
use App\Http\Controllers\Api\RegionController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\GalleryController;
use App\Http\Controllers\Api\MenuItemController;
use App\Http\Controllers\Api\AGSUpdateController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\SiteSettingController;

// API routes (must come before catch-all route)
// A baseline limit for the whole public API. None of these routes are
// authenticated, so without this any of them can be hammered for free.
Route::prefix('api')->middleware('throttle:60,1')->group(function () {
    // Public, read-only. Church writes are managed exclusively through the
    // Filament admin (ChurchResource + ChurchPolicy). Registering the full
    // apiResource here exposed unauthenticated POST/PUT/PATCH/DELETE — and
    // `api/*` is CSRF-exempt in bootstrap/app.php, so anyone could have run
    // `curl -X DELETE /api/churches/{id}`. Nothing consumes the write routes:
    // the SPA only ever GETs (ChurchLocator.vue:474,508,528).
    Route::apiResource('churches', ChurchController::class)->only(['index', 'show']);

    // Additional church-related endpoints
    Route::get('/churches-regions', [ChurchController::class, 'regions']);
    // Region landing pages (requirement 11). Public, read-only.
    Route::get('/regions', [RegionController::class, 'index']);
    Route::get('/regions/{slug}', [RegionController::class, 'show']);

    Route::get('/churches-organizational-regions', [ChurchController::class, 'organizationalRegions']);
    Route::get('/churches-service-days', [ChurchController::class, 'serviceDays']);
    // Each call makes two sequential blocking file_get_contents to NZ Post
    // with a 2s timeout apiece, so one request can hold a PHP-FPM worker for
    // up to 4s. Tighter limit than the group baseline.
    Route::post('/address-search', [ChurchController::class, 'addressSearch'])
        ->middleware('throttle:20,1');

    // CMS page routes
    Route::get('/pages', [PageController::class, 'index']);
    Route::get('/pages/{slug}', [PageController::class, 'show'])->where('slug', '.*');

    // Menu routes
    Route::get('/menu/header', [MenuItemController::class, 'header']);
    Route::get('/menu/footer', [MenuItemController::class, 'footer']);

    // Public events (published only)
    Route::get('/events', [EventController::class, 'index']);
    Route::get('/events/{id}', [EventController::class, 'show']);

    // Assistant General Superintendent updates (published only)
    Route::get('/ags-updates', [AGSUpdateController::class, 'index']);

    // Gallery (public)
    Route::get('/gallery', [GalleryController::class, 'index']);

    // Departments (public, published only)
    Route::get('/departments', [DepartmentController::class, 'index']);
    Route::get('/departments/{slug}', [DepartmentController::class, 'show']);

    // Site-wide settings (logos, socials, footer copy)
    Route::get('/site-settings', [SiteSettingController::class, 'index']);

    // Contact form
    // Unauthenticated write with no captcha or honeypot — without a limit the
    // contact_messages table (and the new admin inbox) can be flooded.
    Route::post('/contact', [ContactController::class, 'store'])
        ->middleware('throttle:5,1');
});

// Frontend routes
Route::get('/', function () {
    return view('app');
})->name('home');

// CMS pages route - accessible at /cms/* (supports nested paths)
Route::get('/cms/{slug}', function () {
    return view('app');
})->where('slug', '.*')->name('cms.page');

// Catch-all route for Vue.js SPA (must be last)
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');

// Admin routes (protected)
Route::prefix('admin')->middleware(['auth'])->group(function () {
    // Admin routes will be handled by Filament
});

require __DIR__.'/auth.php';
