<?php

use App\Models\AGSUpdate;

/*
 * Regression for ags-updates-ignore-published-at: published() only checked
 * is_published, so an update dated in the future went live immediately.
 * A future published_at now schedules it, like department announcements.
 */

function agsUpdate(array $attributes): AGSUpdate
{
    return AGSUpdate::create(array_merge([
        'title' => 'Update',
        'content' => 'Body',
        'is_published' => true,
    ], $attributes));
}

test('a future published_at keeps the update off the public API until then', function () {
    agsUpdate(['title' => 'Live now', 'published_at' => now()->subDay()]);
    agsUpdate(['title' => 'Undated', 'published_at' => null]);
    agsUpdate(['title' => 'Next year', 'published_at' => now()->addYear()]);

    $titles = collect($this->getJson('/api/ags-updates')->assertOk()->json('data'))->pluck('title');

    expect($titles)->toContain('Live now', 'Undated')->not->toContain('Next year');
});

test('a scheduled update goes live once its published_at passes', function () {
    agsUpdate(['title' => 'Scheduled', 'published_at' => now()->addHour()]);

    expect(AGSUpdate::published()->count())->toBe(0);

    $this->travel(2)->hours();

    expect(AGSUpdate::published()->pluck('title')->all())->toBe(['Scheduled']);
});

test('an unpublished update stays hidden whatever its date', function () {
    agsUpdate(['title' => 'Draft', 'is_published' => false, 'published_at' => now()->subDay()]);

    expect(AGSUpdate::published()->count())->toBe(0);
});
