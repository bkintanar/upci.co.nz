<?php

use App\Models\Event;
use App\Models\Region;
use App\Enums\EventScope;

beforeEach(function () {
    $this->north = Region::firstOrCreate(['slug' => 'northern'], ['name' => 'Northern Region', 'sort_order' => 1]);
    $this->south = Region::firstOrCreate(['slug' => 'southern'], ['name' => 'Southern Region', 'sort_order' => 3]);

    $this->national = Event::create([
        'name' => 'General Conference', 'slug' => 'general-conference',
        'start_date' => '2026-09-01', 'is_published' => true,
        'scope' => EventScope::NATIONAL,
    ]);
    $this->northEvent = Event::create([
        'name' => 'Northern Rally', 'slug' => 'northern-rally',
        'start_date' => '2026-09-02', 'is_published' => true,
        'scope' => EventScope::REGIONAL, 'region_id' => $this->north->id,
    ]);
    $this->southEvent = Event::create([
        'name' => 'Southern Rally', 'slug' => 'southern-rally',
        'start_date' => '2026-09-03', 'is_published' => true,
        'scope' => EventScope::REGIONAL, 'region_id' => $this->south->id,
    ]);
});

test('the national calendar excludes regional events', function () {
    // Requirement 9's whole point: these were one undifferentiated list.
    $names = collect($this->getJson('/api/events?scope=national')->json('data'))->pluck('name');

    expect($names)->toContain('General Conference')
        ->and($names)->not->toContain('Northern Rally')
        ->and($names)->not->toContain('Southern Rally');
});

test('a region filter returns only that region\'s events', function () {
    $names = collect($this->getJson('/api/events?region=northern')->json('data'))->pluck('name');

    expect($names)->toContain('Northern Rally')
        ->and($names)->not->toContain('Southern Rally')
        ->and($names)->not->toContain('General Conference');
});

test('an unfiltered listing still returns everything', function () {
    // The filters are additive. Adding scope must not change the default,
    // which is what Events.vue and Calendar.vue already call.
    $names = collect($this->getJson('/api/events')->json('data'))->pluck('name');

    expect($names)->toContain('General Conference', 'Northern Rally', 'Southern Rally');
});

test('an unknown scope is rejected rather than returning an empty list', function () {
    // A silent empty list reads as "no events" and is indistinguishable from
    // a genuinely empty calendar.
    $this->getJson('/api/events?scope=bogus')->assertStatus(422);
});

test('an unknown region is rejected rather than returning an empty list', function () {
    $this->getJson('/api/events?region=bogus')->assertStatus(422);
});

test('the payload carries scope and region', function () {
    $event = collect($this->getJson('/api/events?region=northern')->json('data'))->firstWhere('name', 'Northern Rally');

    expect($event['scope'])->toBe('regional')
        ->and($event['region']['slug'])->toBe('northern')
        ->and($event['region']['name'])->toBe('Northern Region');
});

test('a national event reports a null region rather than omitting the key', function () {
    // The frontend reads event.region directly; a missing key and a null are
    // different failures in JS.
    $event = collect($this->getJson('/api/events?scope=national')->json('data'))->firstWhere('name', 'General Conference');

    expect($event)->toHaveKey('region')
        ->and($event['region'])->toBeNull();
});

test('forRegion scope ignores a department event that carries a region', function () {
    Event::create([
        'name' => 'Youth Camp', 'slug' => 'youth-camp',
        'start_date' => '2026-09-04', 'is_published' => true,
        'scope' => EventScope::DEPARTMENT, 'region_id' => $this->north->id,
    ]);

    $names = Event::forRegion($this->north->id)->pluck('name');

    expect($names)->toContain('Northern Rally')
        ->and($names)->not->toContain('Youth Camp');
});

test('unpublished events stay out of every scope', function () {
    Event::create([
        'name' => 'Draft Rally', 'slug' => 'draft-rally',
        'start_date' => '2026-09-05', 'is_published' => false,
        'scope' => EventScope::REGIONAL, 'region_id' => $this->north->id,
    ]);

    $names = collect($this->getJson('/api/events?region=northern')->json('data'))->pluck('name');

    expect($names)->not->toContain('Draft Rally');
});
