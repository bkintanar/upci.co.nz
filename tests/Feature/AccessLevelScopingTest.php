<?php

use App\Models\User;
use App\Models\Church;
use App\Models\Region;
use App\Enums\UserRole;
use App\Enums\AccessLevel;
use App\Models\Attendance;
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

/*
|--------------------------------------------------------------------------
| An unscoped actor must not match unscoped records
|--------------------------------------------------------------------------
| The access checks compared the actor's region_id / church_id with the
| record's using ===, so a regional user with no region matched every church
| with no region, and a local user with no church matched every orphaned
| attendance row (null === null).
*/

test('a regional user with no region cannot reach a church with no region', function () {
    $unregioned = Church::create(['name' => 'Nowhere', 'region_id' => null, 'is_active' => true]);
    $user = User::create([
        'name' => 'R', 'email' => 'rnull@x', 'password' => 'x',
        'role' => UserRole::REGIONAL_PRESBYTER,
        'access_level' => AccessLevel::REGIONAL,
        'region_id' => null,
    ]);

    expect($user->canAccessChurch($unregioned))->toBeFalse()
        ->and($user->can('view', $unregioned))->toBeFalse()
        ->and($user->can('update', $unregioned))->toBeFalse();
});

test('a regional user with no region cannot update a churchless user', function () {
    $user = User::create([
        'name' => 'R', 'email' => 'rnull@x', 'password' => 'x',
        'role' => UserRole::REGIONAL_PRESBYTER,
        'access_level' => AccessLevel::REGIONAL,
        'region_id' => null,
    ]);
    $churchless = User::create([
        'name' => 'M', 'email' => 'm@x', 'password' => 'x',
        'role' => UserRole::PASTOR,
        'access_level' => AccessLevel::LOCAL,
        'church_id' => null,
    ]);

    expect($user->can('update', $churchless))->toBeFalse()
        ->and($user->can('view', $churchless))->toBeFalse();
});

test('a regional user with no region cannot touch attendance at a church with no region', function () {
    $unregioned = Church::create(['name' => 'Nowhere', 'region_id' => null, 'is_active' => true]);
    $user = User::create([
        'name' => 'R', 'email' => 'rnull@x', 'password' => 'x',
        'role' => UserRole::REGIONAL_PRESBYTER,
        'access_level' => AccessLevel::REGIONAL,
        'region_id' => null,
    ]);
    $row = Attendance::create([
        'church_id' => $unregioned->id, 'user_id' => $user->id, 'date' => now()->toDateString(),
        'mens' => 1, 'ladies' => 0, 'youth' => 0, 'children' => 0, 'visitors' => 0,
    ]);

    expect($user->can('view', $row))->toBeFalse()
        ->and($user->can('update', $row))->toBeFalse();
});

test('a local user with no church cannot view or update an orphaned attendance row', function () {
    $user = User::create([
        'name' => 'L', 'email' => 'lnull@x', 'password' => 'x',
        'role' => UserRole::PASTOR,
        'access_level' => AccessLevel::LOCAL,
        'church_id' => null,
    ]);
    $orphan = Attendance::create([
        'church_id' => null, 'user_id' => $user->id, 'date' => now()->toDateString(),
        'mens' => 1, 'ladies' => 0, 'youth' => 0, 'children' => 0, 'visitors' => 0,
    ]);

    expect($user->can('view', $orphan))->toBeFalse()
        ->and($user->can('update', $orphan))->toBeFalse()
        ->and($user->can('delete', $orphan))->toBeFalse();
});

test('scoped users still reach records in their own scope', function () {
    $regional = User::create([
        'name' => 'R', 'email' => 'rok@x', 'password' => 'x',
        'role' => UserRole::REGIONAL_PRESBYTER,
        'access_level' => AccessLevel::REGIONAL,
        'region_id' => $this->northId,
    ]);
    $local = User::create([
        'name' => 'L', 'email' => 'lok@x', 'password' => 'x',
        'role' => UserRole::PASTOR,
        'access_level' => AccessLevel::LOCAL,
        'church_id' => $this->northChurchA->id,
    ]);
    $row = Attendance::create([
        'church_id' => $this->northChurchA->id, 'user_id' => $local->id, 'date' => now()->toDateString(),
        'mens' => 1, 'ladies' => 0, 'youth' => 0, 'children' => 0, 'visitors' => 0,
    ]);

    expect($regional->can('update', $row))->toBeTrue()
        ->and($local->can('update', $row))->toBeTrue()
        ->and($local->can('view', $row))->toBeTrue()
        ->and($regional->can('update', $this->northChurchB))->toBeTrue();
});
