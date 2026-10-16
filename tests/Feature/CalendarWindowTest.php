<?php

use Carbon\Carbon;
use App\Models\Event;
use App\Models\Region;
use App\Enums\EventScope;
use App\Models\Department;

/*
 * "For the calendars it should show the latest first up until December"
 * (owner, 2026-10-17). The department Calendar, region events and the
 * Calendar of Events list what is not over yet, from New Zealand's today to
 * 31 December, soonest first.
 */
beforeEach(function () {
    // 9am on 17 October in New Zealand is still 16 October in UTC, the
    // app's own timezone: "today" must be New Zealand's.
    $this->travelTo(Carbon::parse('2026-10-17 09:00', 'Pacific/Auckland'));

    // Its own department: the six real ones are installed by a migration.
    $this->dept = Department::create(['name' => 'Calendar Window Dept', 'slug' => 'calendar-window', 'is_published' => true]);
    $this->north = Region::firstOrCreate(['slug' => 'northern'], ['name' => 'Northern Region', 'sort_order' => 1, 'is_published' => true]);

    $event = fn (string $name, string $start, ?string $end = null, array $extra = []) => Event::create(array_merge([
        'name' => $name, 'slug' => str($name)->slug()->toString(),
        'start_date' => $start, 'end_date' => $end, 'is_published' => true,
        'scope' => EventScope::NATIONAL, 'department_id' => $this->dept->id,
    ], $extra));

    $event('Ended yesterday', '2026-10-16');
    $event('Men\'s Conference in April', '2026-04-10', '2026-04-12');
    $event('Running all month', '2026-10-01', '2026-10-22');
    $event('Today', '2026-10-17');
    $event('Online training', '2026-11-14');
    $event('Last day of the year', '2026-12-31');
    $event('Next January', '2027-01-02');
    $event('Unpublished', '2026-10-20', null, ['is_published' => false]);
});

test('the department calendar lists what is still to come this year, soonest first', function () {
    $names = collect($this->getJson('/api/departments/calendar-window')->assertOk()->json('data.events'))->pluck('name')->all();

    expect($names)->toBe(['Running all month', 'Today', 'Online training', 'Last day of the year']);
});

test('the calendar of events asks for the same window', function () {
    $names = collect($this->getJson('/api/events?upcoming=1&department=calendar-window')->assertOk()->json('data'))->pluck('name')->all();

    expect($names)->toBe(['Running all month', 'Today', 'Online training', 'Last day of the year'])
        ->and(file_get_contents(resource_path('js/views/Events.vue')))->toContain("fetch('/api/events?upcoming=1'");
});

test('without the flag the events API still returns every published event', function () {
    // The month grid and the homepage feed set their own ranges.
    $names = collect($this->getJson('/api/events?department=calendar-window')->json('data'))->pluck('name');

    expect($names)->toHaveCount(7)
        ->toContain('Ended yesterday', "Men's Conference in April", 'Next January')
        ->not->toContain('Unpublished');
});

test('a region page lists its own events in the same window', function () {
    foreach ([['North past', '2026-10-10'], ['North next', '2026-10-30'], ['North next year', '2027-02-01']] as [$name, $start]) {
        Event::create([
            'name' => $name, 'slug' => str($name)->slug()->toString(), 'start_date' => $start,
            'is_published' => true, 'scope' => EventScope::REGIONAL, 'region_id' => $this->north->id,
        ]);
    }

    $names = collect($this->getJson('/api/regions/northern')->assertOk()->json('data.events'))->pluck('name')->all();

    expect($names)->toBe(['North next']);
});

test('the window turns over with New Zealand\'s new year, not UTC\'s', function () {
    // 10am on 1 January in New Zealand is still 31 December in UTC.
    $this->travelTo(Carbon::parse('2027-01-01 10:00', 'Pacific/Auckland'));

    $names = collect($this->getJson('/api/departments/calendar-window')->json('data.events'))->pluck('name')->all();

    expect($names)->toBe(['Next January']);
});

test('the to filter includes events on its last day', function () {
    $names = collect($this->getJson('/api/events?department=calendar-window&from=2026-12-01&to=2026-12-31')->json('data'))->pluck('name')->all();

    expect($names)->toBe(['Last day of the year']);
});
