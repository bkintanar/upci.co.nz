<?php

use App\Models\User;
use App\Models\Church;
use App\Models\Region;
use App\Enums\UserRole;
use App\Enums\AccessLevel;

beforeEach(function () {
    $this->regionNorth = Region::firstOrCreate(['slug' => 'north'], ['name' => 'North Region', 'sort_order' => 1]);
    $this->regionSouth = Region::firstOrCreate(['slug' => 'south'], ['name' => 'South Region', 'sort_order' => 3]);
    $this->myChurch = Church::create(['name' => 'Mine', 'region_id' => $this->regionNorth->id, 'is_active' => true]);
    $this->otherChurch = Church::create(['name' => 'Other', 'region_id' => $this->regionSouth->id, 'is_active' => true]);
});

test('local user can update their own church', function () {
    $user = User::create([
        'name' => 'L', 'email' => 'l@x', 'password' => 'x',
        'role' => UserRole::SENIOR_PASTOR,
        'access_level' => AccessLevel::LOCAL,
        'church_id' => $this->myChurch->id,
    ]);

    expect($user->can('update', $this->myChurch))->toBeTrue();
});

test('local user cannot update a different church', function () {
    $user = User::create([
        'name' => 'L', 'email' => 'l@x', 'password' => 'x',
        'role' => UserRole::SENIOR_PASTOR,
        'access_level' => AccessLevel::LOCAL,
        'church_id' => $this->myChurch->id,
    ]);

    expect($user->can('update', $this->otherChurch))->toBeFalse();
});

test('local user without church_id cannot update any church', function () {
    $user = User::create([
        'name' => 'L', 'email' => 'l@x', 'password' => 'x',
        'role' => UserRole::SENIOR_PASTOR,
        'access_level' => AccessLevel::LOCAL,
        'church_id' => null,
    ]);

    expect($user->can('update', $this->myChurch))->toBeFalse();
});

test('local user still cannot create a church', function () {
    $user = User::create([
        'name' => 'L', 'email' => 'l@x', 'password' => 'x',
        'role' => UserRole::SENIOR_PASTOR,
        'access_level' => AccessLevel::LOCAL,
        'church_id' => $this->myChurch->id,
    ]);

    expect($user->can('create', Church::class))->toBeFalse();
});

test('local user still cannot delete their church', function () {
    $user = User::create([
        'name' => 'L', 'email' => 'l@x', 'password' => 'x',
        'role' => UserRole::SENIOR_PASTOR,
        'access_level' => AccessLevel::LOCAL,
        'church_id' => $this->myChurch->id,
    ]);

    expect($user->can('delete', $this->myChurch))->toBeFalse();
});

test('regional user can update churches in their region', function () {
    $user = User::create([
        'name' => 'R', 'email' => 'r@x', 'password' => 'x',
        'role' => UserRole::REGIONAL_PRESBYTER,
        'access_level' => AccessLevel::REGIONAL,
        'region_id' => $this->regionNorth->id,
    ]);

    expect($user->can('update', $this->myChurch))->toBeTrue()
        ->and($user->can('update', $this->otherChurch))->toBeFalse();
});

test('national user can update any church', function () {
    $user = User::create([
        'name' => 'N', 'email' => 'n@x', 'password' => 'x',
        'role' => UserRole::ADMINISTRATOR,
        'access_level' => AccessLevel::NATIONAL,
    ]);

    expect($user->can('update', $this->myChurch))->toBeTrue()
        ->and($user->can('update', $this->otherChurch))->toBeTrue();
});
