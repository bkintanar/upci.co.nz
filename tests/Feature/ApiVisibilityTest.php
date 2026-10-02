<?php

use App\Models\Event;
use App\Models\Region;
use App\Enums\EventScope;
use App\Models\Department;
use App\Models\GalleryItem;

/*
 * Regressions for draft-owner-content-via-filters (a draft department or
 * region was reachable through the public gallery and event filters) and
 * api-validation-redirects-not-422 (API errors came back as HTML).
 */

beforeEach(function () {
    $this->liveDept = Department::create(['name' => 'Audit Live Dept', 'slug' => 'audit-live', 'is_published' => true]);
    $this->draftDept = Department::create(['name' => 'Audit Draft Dept', 'slug' => 'audit-draft', 'is_published' => false]);
    $this->liveRegion = Region::create(['name' => 'Audit Live Region', 'slug' => 'audit-live-region', 'is_published' => true]);
    $this->draftRegion = Region::create(['name' => 'Audit Draft Region', 'slug' => 'audit-draft-region', 'is_published' => false]);

    foreach ([
        ['Live dept photo', 'department', $this->liveDept],
        ['Draft dept photo', 'department', $this->draftDept],
        ['Live region photo', 'region', $this->liveRegion],
        ['Draft region photo', 'region', $this->draftRegion],
    ] as [$title, $type, $owner]) {
        GalleryItem::create([
            'title' => $title, 'image_path' => 'gallery/x.jpg', 'is_published' => true,
            'galleryable_type' => $type, 'galleryable_id' => $owner->id,
        ]);
    }

    $event = fn (string $name, array $extra = []) => Event::create(array_merge([
        'name' => $name, 'slug' => str($name)->slug()->toString(),
        'start_date' => '2026-10-01', 'is_published' => true,
        'scope' => EventScope::NATIONAL,
    ], $extra));

    $this->ownerless = $event('Audit Ownerless Event');
    $this->liveDeptEvent = $event('Audit Live Dept Event', ['department_id' => $this->liveDept->id]);
    $this->draftDeptEvent = $event('Audit Draft Dept Event', ['department_id' => $this->draftDept->id]);
    $this->liveRegionEvent = $event('Audit Live Region Event', ['scope' => EventScope::REGIONAL, 'region_id' => $this->liveRegion->id]);
    $this->draftRegionEvent = $event('Audit Draft Region Event', ['scope' => EventScope::REGIONAL, 'region_id' => $this->draftRegion->id]);
});

// --- Gallery -------------------------------------------------------------

test('a published department gallery still loads', function () {
    $titles = collect($this->getJson('/api/gallery?department=audit-live')->assertOk()->json('data'))->pluck('title');

    expect($titles)->toContain('Live dept photo')->and($titles)->not->toContain('Draft dept photo');
});

test('a published region gallery still loads', function () {
    $titles = collect($this->getJson('/api/gallery?region=audit-live-region')->assertOk()->json('data'))->pluck('title');

    expect($titles)->toContain('Live region photo')->and($titles)->not->toContain('Draft region photo');
});

test('a draft department slug gets the same 422 as a nonexistent one', function () {
    $draft = $this->getJson('/api/gallery?department=audit-draft')->assertStatus(422);
    $missing = $this->getJson('/api/gallery?department=no-such-dept')->assertStatus(422);

    expect($draft->json())->toBe($missing->json())
        ->and(json_encode($draft->json()))->not->toContain('Draft dept photo');
});

test('a draft region slug gets the same 422 as a nonexistent one', function () {
    $draft = $this->getJson('/api/gallery?region=audit-draft-region')->assertStatus(422);
    $missing = $this->getJson('/api/gallery?region=no-such-region')->assertStatus(422);

    expect($draft->json())->toBe($missing->json());
});

// --- Events --------------------------------------------------------------

test('the event listing leaves out events owned by a draft department or region', function () {
    $names = collect($this->getJson('/api/events')->assertOk()->json('data'))->pluck('name');

    expect($names)->toContain('Audit Ownerless Event', 'Audit Live Dept Event', 'Audit Live Region Event')
        ->and($names)->not->toContain('Audit Draft Dept Event', 'Audit Draft Region Event');
});

test('the event listing never names a draft department', function () {
    $body = $this->getJson('/api/events')->assertOk()->getContent();

    expect($body)->not->toContain('Audit Draft Dept')
        ->and($body)->not->toContain('Audit Draft Region');
});

test('filtering events by a draft department returns nothing', function () {
    $names = collect($this->getJson('/api/events?department=audit-draft')->assertOk()->json('data'))->pluck('name');

    expect($names)->toBeEmpty();
});

test('filtering events by a published department still works', function () {
    $names = collect($this->getJson('/api/events?department=audit-live')->assertOk()->json('data'))->pluck('name');

    expect($names->all())->toBe(['Audit Live Dept Event']);
});

test('a draft region slug in the event filter gets the same 422 as a nonexistent one', function () {
    $draft = $this->getJson('/api/events?region=audit-draft-region')->assertStatus(422);
    $missing = $this->getJson('/api/events?region=no-such-region')->assertStatus(422);

    expect($draft->json())->toBe($missing->json());
});

test('a published region filter still returns its events', function () {
    $names = collect($this->getJson('/api/events?region=audit-live-region')->assertOk()->json('data'))->pluck('name');

    expect($names->all())->toBe(['Audit Live Region Event']);
});

test('a single event owned by a draft department or region is not found', function () {
    $this->getJson("/api/events/{$this->draftDeptEvent->id}")->assertNotFound();
    $this->getJson("/api/events/{$this->draftRegionEvent->id}")->assertNotFound();

    $this->getJson("/api/events/{$this->liveDeptEvent->id}")->assertOk()->assertJsonPath('data.name', 'Audit Live Dept Event');
    $this->getJson("/api/events/{$this->ownerless->id}")->assertOk();
});

// --- JSON errors without an Accept header ---------------------------------

test('an API validation error is a 422 JSON body even without an Accept header', function () {
    // get(), not getJson(): getJson() sends Accept: application/json, which
    // is exactly what the SPA's fetch() calls did not do.
    $this->get('/api/gallery?department=nope')
        ->assertStatus(422)
        ->assertHeader('Content-Type', 'application/json')
        ->assertJsonStructure(['message', 'errors' => ['department']]);
});

test('an event filter validation error is JSON without an Accept header', function () {
    $this->get('/api/events?scope=bogus')
        ->assertStatus(422)
        ->assertJsonStructure(['message', 'errors' => ['scope']]);
});

test('an API throttle rejection is JSON without an Accept header', function () {
    for ($i = 0; $i < 60; $i++) {
        $this->get('/api/menu/footer')->assertOk();
    }

    $this->get('/api/menu/footer')
        ->assertStatus(429)
        ->assertHeader('Content-Type', 'application/json')
        ->assertJsonStructure(['message']);
});
