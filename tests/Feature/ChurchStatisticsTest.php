<?php

use App\Models\Church;
use App\Models\Region;

beforeEach(function () {
    $this->north = Region::firstOrCreate(['slug' => 'northern'], ['name' => 'Northern Region', 'sort_order' => 1]);
});

test('statistics are counted from records, not authored', function () {
    // The homepage stated four figures as prose and three had drifted: 10
    // established churches against 9, 3 daughter works against none, 2
    // preaching points against 1, 12 potential home groups against none.
    Church::create(['name' => 'A', 'church_status' => 'Established Church', 'is_active' => true, 'region_id' => $this->north->id]);
    Church::create(['name' => 'B', 'church_status' => 'Established Church', 'is_active' => true, 'region_id' => $this->north->id]);
    Church::create(['name' => 'C', 'church_status' => 'Preaching Point', 'is_active' => true, 'region_id' => $this->north->id]);

    $stats = collect($this->getJson('/api/church-statistics')->json('data'))->pluck('value', 'label');

    expect($stats['Established Churches'])->toBe(2)
        ->and($stats['Preaching Point'])->toBe(1);
});

test('an inactive church is not counted', function () {
    Church::create(['name' => 'Live', 'church_status' => 'Established Church', 'is_active' => true, 'region_id' => $this->north->id]);
    Church::create(['name' => 'Closed', 'church_status' => 'Established Church', 'is_active' => false, 'region_id' => $this->north->id]);

    $stats = collect($this->getJson('/api/church-statistics')->json('data'))->pluck('value', 'label');

    expect($stats['Established Church'])->toBe(1);
});

test('a category with no records is omitted rather than published as zero', function () {
    // "Daughter Works" was claimed as 3 and does not exist in the data at all.
    // A statistic nobody has is not a statistic worth publishing.
    Church::create(['name' => 'A', 'church_status' => 'Established Church', 'is_active' => true, 'region_id' => $this->north->id]);

    $labels = collect($this->getJson('/api/church-statistics')->json('data'))->pluck('label');

    expect($labels)->not->toContain('Daughter Works')
        ->and($labels)->not->toContain('Potential Home Groups');
});

test('the label pluralises with its count', function () {
    Church::create(['name' => 'Only one', 'church_status' => 'Established Church', 'is_active' => true, 'region_id' => $this->north->id]);

    $labels = collect($this->getJson('/api/church-statistics')->json('data'))->pluck('label');

    expect($labels)->toContain('Established Church')
        ->and($labels)->not->toContain('Established Churches');
});
