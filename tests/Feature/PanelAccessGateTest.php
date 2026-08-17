<?php

use App\Models\User;
use App\Models\Church;
use App\Models\Region;
use App\Enums\UserRole;
use App\Enums\AccessLevel;
use Filament\Facades\Filament;

beforeEach(function () {
    $this->panel = Filament::getPanel('admin');
    $this->region = Region::firstOrCreate(['slug' => 'northern'], ['name' => 'Northern Region', 'sort_order' => 1]);
    $this->church = Church::create(['name' => 'Test Church', 'region_id' => $this->region->id, 'is_active' => true]);
});

test('user with null access_level cannot access panel', function () {
    $user = User::create([
        'name' => 'Member', 'email' => 'm@x', 'password' => 'x',
        'role' => UserRole::MEMBER, 'access_level' => null,
    ]);

    expect($user->canAccessPanel($this->panel))->toBeFalse();
});

test('national user can access panel', function () {
    $user = User::create([
        'name' => 'Admin', 'email' => 'a@x', 'password' => 'x',
        'role' => UserRole::ADMINISTRATOR, 'access_level' => AccessLevel::NATIONAL,
    ]);

    expect($user->canAccessPanel($this->panel))->toBeTrue();
});

test('regional user can access panel', function () {
    $user = User::create([
        'name' => 'Presbyter', 'email' => 'p@x', 'password' => 'x',
        'role' => UserRole::REGIONAL_PRESBYTER,
        'access_level' => AccessLevel::REGIONAL,
        'region_id' => $this->region->id,
    ]);

    expect($user->canAccessPanel($this->panel))->toBeTrue();
});

test('local user can access panel', function () {
    $user = User::create([
        'name' => 'Pastor', 'email' => 'l@x', 'password' => 'x',
        'role' => UserRole::SENIOR_PASTOR,
        'access_level' => AccessLevel::LOCAL,
        'church_id' => $this->church->id,
    ]);

    expect($user->canAccessPanel($this->panel))->toBeTrue();
});
