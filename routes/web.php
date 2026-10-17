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
use App\Http\Controllers\Api\AbcEnrolmentController;
use App\Http\Controllers\Api\AbcPastorReferenceController;

// API routes (must come before catch-all route)
// A baseline limit for the whole public API. None of these routes are
// authenticated, so without this any of them can be hammered for free.
Route::prefix('api')->middleware('throttle:60,1')->group(function () {
    // Public, read-only. Church writes are managed exclusively through the
    // Filament admin (ChurchResource + ChurchPolicy). Registering the full
    // apiResource here exposed unauthenticated POST/PUT/PATCH/DELETE — and
    // `api/*` is CSRF-exempt in bootstrap/app.php, so anyone could have run
    // `curl -X DELETE /api/churches/{id}`. Nothing consumes the write routes:
    // the SPA only ever GETs (components/blocks/ChurchLocatorBlock.vue).
    Route::apiResource('churches', ChurchController::class)->only(['index', 'show']);

    // Additional church-related endpoints
    Route::get('/churches-regions', [ChurchController::class, 'regions']);
    // Region landing pages (requirement 11). Public, read-only.
    Route::get('/regions', [RegionController::class, 'index']);
    Route::get('/regions/{slug}', [RegionController::class, 'show']);

    Route::get('/church-statistics', [ChurchController::class, 'statistics']);
    Route::get('/churches-organizational-regions', [ChurchController::class, 'organizationalRegions']);
    Route::get('/churches-service-days', [ChurchController::class, 'serviceDays']);
    // Each call makes two sequential blocking file_get_contents to NZ Post
    // with a 2s timeout apiece, so one request can hold a PHP-FPM worker for
    // up to 4s. Tighter limit than the group baseline.
    //
    // Every route-level limit below names its own counter (the third throttle
    // argument). Unnamed, `throttle:N,1` keys on domain|ip -- the same key the
    // group's 60/min baseline uses -- so the menu, settings and page reads the
    // SPA makes on every page load were counted against these limits: two
    // page views and a visitor's contact message came back 429.
    Route::post('/address-search', [ChurchController::class, 'addressSearch'])
        ->middleware('throttle:20,1,address-search');

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
        ->middleware('throttle:5,1,contact');

    // ABC first-time student enrolment. An unauthenticated write of personal
    // data, with an optional file upload, so it gets the contact form's limit.
    Route::post('/abc-enrolments', [AbcEnrolmentController::class, 'store'])
        ->middleware('throttle:5,1,abc-enrolment');
    // The church dropdown's pastors: names only, never email addresses.
    Route::get('/abc-enrolments/pastors', [AbcEnrolmentController::class, 'pastors']);

    // The pastor's reference questionnaire, reached only through the link
    // emailed to the pastor. Its own counters: reading is limited so the
    // token cannot be guessed at speed, and sending like the forms above.
    Route::get('/abc-pastor-references/{token}', [AbcPastorReferenceController::class, 'show'])
        ->middleware('throttle:30,1,abc-pastor-reference');
    Route::post('/abc-pastor-references/{token}', [AbcPastorReferenceController::class, 'store'])
        ->middleware('throttle:10,1,abc-pastor-reference-submit');
});

// Frontend routes
Route::get('/', function () {
    return view('app');
})->name('home');

// Auth redirects (login, forgot-password, logout, ...). These must be
// registered before the `/cms/{slug}` and `/{any}` catch-alls, otherwise the
// SPA catch-all shadows them and `/login` serves the Vue app instead of
// redirecting to the Filament panel.
require __DIR__.'/auth.php';

// CMS pages route - accessible at /cms/* (supports nested paths)
Route::get('/cms/{slug}', function () {
    return view('app');
})->where('slug', '.*')->name('cms.page');

// Catch-all route for Vue.js SPA (must be last). It excludes `api` and
// `api/*` so unknown API paths are a real 404 (rendered as JSON by the
// exception handler) instead of the SPA's HTML with a 200.
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api(/|$)).*');

// Admin routes (protected)
Route::prefix('admin')->middleware(['auth'])->group(function () {
    // Admin routes will be handled by Filament
});
