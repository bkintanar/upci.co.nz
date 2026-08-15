<?php

use App\Models\User;
use App\Models\Event;
use App\Models\Church;
use App\Models\Region;
use App\Enums\UserRole;
use App\Enums\AccessLevel;

beforeEach(function () {
    $this->region = Region::firstOrCreate(['slug' => 'north'], ['name' => 'North Region', 'sort_order' => 1]);
    $this->church = Church::create(['name' => 'Test Church', 'region_id' => $this->region->id, 'is_active' => true]);
});

function makeUser(string $level, array $extra = []): User
{
    $defaults = [
        'name' => 'T', 'email' => $level.'@x', 'password' => 'x',
        'role' => UserRole::ADMINISTRATOR,
        'access_level' => $level === 'null' ? null : AccessLevel::from($level),
    ];

    return User::create(array_merge($defaults, $extra));
}

test('local user cannot viewAny events', function () {
    $user = makeUser('local', ['church_id' => $this->church->id]);

    expect($user->can('viewAny', Event::class))->toBeFalse();
});

test('national user can viewAny events', function () {
    $user = makeUser('national');

    expect($user->can('viewAny', Event::class))->toBeTrue();
});

test('regional user can viewAny events', function () {
    $user = makeUser('regional', ['region_id' => $this->region->id]);

    expect($user->can('viewAny', Event::class))->toBeTrue();
});

test('local user cannot view an event record', function () {
    $user = makeUser('local', ['church_id' => $this->church->id]);
    $event = Event::create(['name' => 'T', 'slug' => 't-'.uniqid(), 'start_date' => now(), 'end_date' => now()->addDay()]);

    expect($user->can('view', $event))->toBeFalse();
});

test('local user hitting /admin/events receives 404', function () {
    $user = makeUser('local', ['church_id' => $this->church->id]);

    $this->actingAs($user)
        ->get('/admin/events')
        ->assertStatus(404);
});

test('national user hitting /admin/events receives 200', function () {
    $user = makeUser('national');

    $this->actingAs($user)
        ->get('/admin/events')
        ->assertStatus(200);
});

test('regional user hitting /admin/events receives 200', function () {
    $user = makeUser('regional', ['region_id' => $this->region->id]);

    $this->actingAs($user)
        ->get('/admin/events')
        ->assertStatus(200);
});
