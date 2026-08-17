<?php

use App\Models\User;
use App\Models\Church;
use App\Models\Region;
use App\Enums\UserRole;
use App\Enums\AccessLevel;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Churches\ChurchResource;
use App\Filament\Resources\Attendances\AttendanceResource;

beforeEach(function () {
    // regions table is populated by migration; re-seed in :memory: DB
    $this->northId = Region::firstOrCreate(['slug' => 'northern'], ['name' => 'Northern Region',   'sort_order' => 1])->id;
    $this->centralId = Region::firstOrCreate(['slug' => 'central'], ['name' => 'Central Region', 'sort_order' => 2])->id;
    $this->southId = Region::firstOrCreate(['slug' => 'southern'], ['name' => 'Southern Region',   'sort_order' => 3])->id;

    $this->northChurchA = Church::create(['name' => 'North A', 'region_id' => $this->northId, 'is_active' => true]);
    $this->northChurchB = Church::create(['name' => 'North B', 'region_id' => $this->northId, 'is_active' => true]);
    $this->centralChurch = Church::create(['name' => 'Central C', 'region_id' => $this->centralId, 'is_active' => true]);
    $this->southChurch = Church::create(['name' => 'South D', 'region_id' => $this->southId, 'is_active' => true]);
});

test('national user sees every church', function () {
    $user = User::create([
        'name' => 'N', 'email' => 'n@x', 'password' => 'x',
        'role' => UserRole::ADMINISTRATOR, 'access_level' => AccessLevel::NATIONAL,
    ]);
    $this->actingAs($user);

    expect(ChurchResource::getEloquentQuery()->count())->toBe(4);
});

test('regional user only sees churches in their region', function () {
    $user = User::create([
        'name' => 'R', 'email' => 'r@x', 'password' => 'x',
        'role' => UserRole::REGIONAL_PRESBYTER,
        'access_level' => AccessLevel::REGIONAL,
        'region_id' => $this->northId,
    ]);
    $this->actingAs($user);

    $ids = ChurchResource::getEloquentQuery()->pluck('id')->sort()->values()->all();
    expect($ids)->toBe([$this->northChurchA->id, $this->northChurchB->id]);
});

test('local user only sees their church', function () {
    $user = User::create([
        'name' => 'L', 'email' => 'l@x', 'password' => 'x',
        'role' => UserRole::PASTOR,
        'access_level' => AccessLevel::LOCAL,
        'church_id' => $this->centralChurch->id,
    ]);
    $this->actingAs($user);

    $ids = ChurchResource::getEloquentQuery()->pluck('id')->all();
    expect($ids)->toBe([$this->centralChurch->id]);
});

test('user with null access_level sees nothing', function () {
    $user = User::create([
        'name' => 'X', 'email' => 'x@x', 'password' => 'x',
        'role' => UserRole::MEMBER,
    ]);
    $this->actingAs($user);

    expect(ChurchResource::getEloquentQuery()->count())->toBe(0);
    expect(UserResource::getEloquentQuery()->count())->toBe(0);
    expect(AttendanceResource::getEloquentQuery()->count())->toBe(0);
});

test('regional user sees users belonging to churches in their region', function () {
    $ownerInNorthA = User::create([
        'name' => 'ownerA', 'email' => 'a@x', 'password' => 'x',
        'role' => UserRole::PASTOR, 'access_level' => AccessLevel::LOCAL,
        'church_id' => $this->northChurchA->id,
    ]);
    $ownerInCentral = User::create([
        'name' => 'ownerC', 'email' => 'c@x', 'password' => 'x',
        'role' => UserRole::PASTOR, 'access_level' => AccessLevel::LOCAL,
        'church_id' => $this->centralChurch->id,
    ]);

    $regional = User::create([
        'name' => 'R', 'email' => 'r@x', 'password' => 'x',
        'role' => UserRole::REGIONAL_PRESBYTER,
        'access_level' => AccessLevel::REGIONAL,
        'region_id' => $this->northId,
    ]);
    $this->actingAs($regional);

    $ids = UserResource::getEloquentQuery()->pluck('id')->all();
    expect($ids)->toContain($ownerInNorthA->id);
    expect($ids)->not->toContain($ownerInCentral->id);
});

test('national user policy permits create on churches', function () {
    $user = User::create([
        'name' => 'N', 'email' => 'n@x', 'password' => 'x',
        'role' => UserRole::ADMINISTRATOR, 'access_level' => AccessLevel::NATIONAL,
    ]);
    expect($user->can('create', Church::class))->toBeTrue();
});

test('regional user policy denies create on churches', function () {
    $user = User::create([
        'name' => 'R', 'email' => 'r@x', 'password' => 'x',
        'role' => UserRole::REGIONAL_PRESBYTER,
        'access_level' => AccessLevel::REGIONAL,
        'region_id' => $this->northId,
    ]);
    expect($user->can('create', Church::class))->toBeFalse();
});

test('local user can update their own church but not others', function () {
    $user = User::create([
        'name' => 'L', 'email' => 'l@x', 'password' => 'x',
        'role' => UserRole::PASTOR,
        'access_level' => AccessLevel::LOCAL,
        'church_id' => $this->northChurchA->id,
    ]);
    expect($user->can('update', $this->northChurchA))->toBeTrue();
    expect($user->can('update', $this->centralChurch))->toBeFalse();
});

test('regional user can update churches in their region', function () {
    $user = User::create([
        'name' => 'R', 'email' => 'r@x', 'password' => 'x',
        'role' => UserRole::REGIONAL_PRESBYTER,
        'access_level' => AccessLevel::REGIONAL,
        'region_id' => $this->northId,
    ]);
    expect($user->can('update', $this->northChurchA))->toBeTrue();
    expect($user->can('update', $this->centralChurch))->toBeFalse();
});
