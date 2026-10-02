<?php

use App\Models\User;
use App\Models\Church;
use App\Models\Region;
use App\Enums\UserRole;
use App\Enums\AccessLevel;

// The two starter-kit cases below target `/dashboard`, which doesn't exist in
// this app: the admin dashboard is the Filament panel at `/admin`. They are
// kept (skipped) for reference; the `/admin` cases after them replace them.

test('guests are redirected to the login page', function () {
    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
})->skip('Stale starter-kit test: there is no /dashboard route; the dashboard is the Filament panel at /admin (covered by the /admin tests below).');

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertStatus(200);
})->skip('Stale starter-kit test: there is no /dashboard route; the dashboard is the Filament panel at /admin (covered by the /admin tests below).');

test('guests are redirected from the admin dashboard to the admin login', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

test('a user with an access level can reach the admin dashboard', function () {
    $region = Region::firstOrCreate(['slug' => 'northern'], ['name' => 'Northern Region', 'sort_order' => 1]);
    $church = Church::create(['name' => 'Test Church', 'region_id' => $region->id, 'is_active' => true]);

    $user = User::factory()->create([
        'role' => UserRole::SENIOR_PASTOR,
        'access_level' => AccessLevel::LOCAL,
        'church_id' => $church->id,
    ]);

    $this->actingAs($user)->get('/admin')->assertOk();
});

test('a national user can reach the admin dashboard', function () {
    $user = User::factory()->create([
        'role' => UserRole::ADMINISTRATOR,
        'access_level' => AccessLevel::NATIONAL,
    ]);

    $this->actingAs($user)->get('/admin')->assertOk();
});

test('a user with a NULL access_level is forbidden from the admin dashboard', function () {
    $user = User::factory()->create([
        'role' => UserRole::MEMBER,
        'access_level' => null,
    ]);

    $this->actingAs($user)->get('/admin')->assertForbidden();
});
