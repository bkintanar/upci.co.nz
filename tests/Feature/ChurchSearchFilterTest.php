<?php

use App\Models\Church;
use App\Models\Region;

/**
 * The filter contract the church locator depends on.
 *
 * The homepage's finder hands its term to the locator through the URL as
 * `?search=`, and the locator turns that into `/api/churches?search=`. For a
 * long time the locator never read the URL, so the term was pushed into the
 * address bar and silently dropped — the visitor got the full list back with an
 * empty search box, which reads as "there are ten churches" rather than "your
 * search was ignored".
 *
 * These tests pin the ENDPOINT half of that contract. They cannot cover the
 * wiring that was actually broken: that lives in Vue, and this project has no
 * JS test runner. The wiring is verified in a browser instead — every defect of
 * this shape here has passed lint, tests and build while the page was broken.
 */
beforeEach(function () {
    $this->northern = Region::firstOrCreate(
        ['slug' => 'northern'],
        ['name' => 'Northern Region', 'sort_order' => 1]
    );
    $this->southern = Region::firstOrCreate(
        ['slug' => 'southern'],
        ['name' => 'Southern Region', 'sort_order' => 3]
    );

    // index() requires coordinates, so a church without lat/lng is invisible to
    // the endpoint and any assertion over it would pass vacuously.
    $this->auckland = Church::create([
        'name' => 'Auckland Test Church',
        'city' => 'Auckland',
        'region_id' => $this->northern->id,
        'latitude' => -36.85,
        'longitude' => 174.76,
        'is_active' => true,
    ]);

    $this->christchurch = Church::create([
        'name' => 'Christchurch Test Church',
        'city' => 'Christchurch',
        'region_id' => $this->southern->id,
        'latitude' => -43.53,
        'longitude' => 172.63,
        'is_active' => true,
    ]);
});

it('filters churches by the search term', function () {
    $names = collect($this->getJson('/api/churches?search=Christchurch')
        ->assertOk()
        ->json('data'))
        ->pluck('name');

    expect($names)->toContain('Christchurch Test Church')
        ->and($names)->not->toContain('Auckland Test Church');
});

it('filters churches by organisational region', function () {
    $names = collect($this->getJson('/api/churches?organizational_region=southern')
        ->assertOk()
        ->json('data'))
        ->pluck('name');

    expect($names)->toContain('Christchurch Test Church')
        ->and($names)->not->toContain('Auckland Test Church');
});

it('returns every church when no filter is given', function () {
    $names = collect($this->getJson('/api/churches')
        ->assertOk()
        ->json('data'))
        ->pluck('name');

    expect($names)->toContain('Christchurch Test Church')
        ->and($names)->toContain('Auckland Test Church');
});

it('returns an empty set rather than everything for a term that matches nothing', function () {
    // The failure this guards against is a filter being dropped somewhere in the
    // chain, which returns the FULL list and looks like a working search.
    $data = $this->getJson('/api/churches?search=ZzzNoSuchTown')
        ->assertOk()
        ->json('data');

    expect($data)->toBeEmpty();
});

/*
 * Service-day filter. The locator's day filter is a multi-select, but the
 * endpoint used to read `service_day` as a scalar, so PHP kept only the last
 * value and a Sunday + Wednesday selection returned Wednesday churches only.
 */
function serviceDayChurches(): array
{
    $region = Region::firstOrCreate(['slug' => 'northern'], ['name' => 'Northern Region', 'sort_order' => 1]);

    $make = fn (string $name, array $days) => Church::create([
        'name' => $name,
        'city' => 'Hamilton',
        'region_id' => $region->id,
        'latitude' => -37.78,
        'longitude' => 175.28,
        'is_active' => true,
        'service_times' => [['service_type' => 'Service', 'time' => '10:00 AM', 'days' => $days]],
    ]);

    return [
        'sunday' => $make('Sunday Only Church', ['sunday']),
        'wednesday' => $make('Wednesday Only Church', ['wednesday']),
        'friday' => $make('Friday Only Church', ['friday']),
    ];
}

it('matches churches on ANY of several selected service days', function () {
    serviceDayChurches();

    $response = $this->getJson('/api/churches?service_day[]=sunday&service_day[]=wednesday')->assertOk();
    $names = collect($response->json('data'))->pluck('name');

    expect($names)->toContain('Sunday Only Church')
        ->and($names)->toContain('Wednesday Only Church')
        ->and($names)->not->toContain('Friday Only Church')
        // Churches with no service times at all must not match a day filter.
        ->and($names)->not->toContain('Auckland Test Church');

    expect($response->json('filters.service_day'))->toBe(['sunday', 'wednesday']);
});

it('still accepts a single scalar service day from older shared links', function () {
    serviceDayChurches();

    $names = collect($this->getJson('/api/churches?service_day=sunday')->assertOk()->json('data'))->pluck('name');

    expect($names)->toContain('Sunday Only Church')
        ->and($names)->not->toContain('Wednesday Only Church')
        ->and($names)->not->toContain('Friday Only Church');
});

it('matches service days case-insensitively', function () {
    serviceDayChurches();

    $names = collect($this->getJson('/api/churches?service_day[]=Wednesday')->assertOk()->json('data'))->pluck('name');

    expect($names)->toContain('Wednesday Only Church')
        ->and($names)->not->toContain('Sunday Only Church');
});

it('ignores an empty service day rather than filtering everything out', function () {
    serviceDayChurches();

    $names = collect($this->getJson('/api/churches?service_day=')->assertOk()->json('data'))->pluck('name');

    expect($names)->toContain('Friday Only Church')
        ->and($names)->toContain('Auckland Test Church');
});
