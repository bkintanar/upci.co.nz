<?php

use App\Models\Department;
use App\Models\DepartmentAnnouncement;

beforeEach(function () {
    $this->department = Department::firstOrCreate(['slug' => 'mens'], ['name' => "National Men's Department"]);
});

function announce(array $attrs = []): DepartmentAnnouncement
{
    return DepartmentAnnouncement::create(array_merge([
        'department_id' => test()->department->id,
        'title' => 'Untitled',
        'content' => 'Body',
        'published_at' => now()->subDay(),
        'is_published' => true,
        'sort_order' => 0,
    ], $attrs));
}

function publicTitles(): \Illuminate\Support\Collection
{
    return collect(test()->getJson('/api/departments/mens')->json('data.announcements'))->pluck('title');
}

test('a future dated announcement is withheld until its date arrives', function () {
    // published_at was authorable, cast, ordered by and displayed — but never
    // gated visibility, so scheduling an announcement published it instantly.
    announce(['title' => 'Next year', 'published_at' => now()->addYear()]);
    announce(['title' => 'Yesterday']);

    expect(publicTitles())->toContain('Yesterday')
        ->and(publicTitles())->not->toContain('Next year');
});

test('an announcement with no date is treated as published', function () {
    // Null means "no schedule set", which is what is_published alone already
    // meant. Treating null as pending would retroactively hide existing rows.
    announce(['title' => 'Undated', 'published_at' => null]);

    expect(publicTitles())->toContain('Undated');
});

test('an unpublished announcement stays hidden regardless of date', function () {
    announce(['title' => 'Draft', 'is_published' => false]);

    expect(publicTitles())->not->toContain('Draft');
});

test('sort_order takes precedence over date', function () {
    // The admin offers a sort_order field and nothing consulted it, so an
    // author could reorder and see no change.
    announce(['title' => 'Older but pinned', 'published_at' => now()->subYear(), 'sort_order' => 1]);
    announce(['title' => 'Newer', 'published_at' => now()->subDay(), 'sort_order' => 5]);

    expect(publicTitles()->take(2)->all())->toBe(['Older but pinned', 'Newer']);
});

test('equal sort_order falls back to newest first', function () {
    announce(['title' => 'Older', 'published_at' => now()->subYear(), 'sort_order' => 0]);
    announce(['title' => 'Newer', 'published_at' => now()->subDay(), 'sort_order' => 0]);

    expect(publicTitles()->take(2)->all())->toBe(['Newer', 'Older']);
});

test('isScheduled distinguishes a pending announcement from a live one', function () {
    expect(announce(['published_at' => now()->addWeek()])->isScheduled())->toBeTrue()
        ->and(announce(['published_at' => now()->subWeek()])->isScheduled())->toBeFalse()
        ->and(announce(['published_at' => null])->isScheduled())->toBeFalse()
        ->and(announce(['published_at' => now()->addWeek(), 'is_published' => false])->isScheduled())->toBeFalse();
});
