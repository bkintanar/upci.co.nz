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
