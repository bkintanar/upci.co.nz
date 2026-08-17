<?php

use App\Models\MenuItem;
use App\Models\SiteSetting;

function headerLabels(): array
{
    return collect(test()->getJson('/api/menu/header')->json('data'))->pluck('label')->all();
}

function headerChildLabels(): array
{
    return collect(test()->getJson('/api/menu/header')->json('data'))
        ->flatMap(fn ($item) => collect($item['children'] ?? [])->pluck('label'))
        ->all();
}

test('the header offers Gallery and Regions as their own items', function () {
    // Requirement 7. Gallery used to be a child of About pointing at
    // /departments#gallery — an anchor on a different page, not a gallery.
    expect(headerLabels())->toContain('Gallery', 'Regions');
});

test('gallery points at the gallery page, not an anchor on another page', function () {
    $gallery = collect($this->getJson('/api/menu/header')->json('data'))->firstWhere('label', 'Gallery');

    expect($gallery['url'])->toBe('/gallery');
});

test('the General Superintendent link is gone from the menu', function () {
    expect(headerChildLabels())->not->toContain('General Superintendent')
        ->and(headerLabels())->not->toContain('General Superintendent');
});

test('SBQ and JBQ sit under Youth and Childrens', function () {
    // Requirements 7 and 10. Asserted on URL, not label: the labels carry the
    // full programme name and are presentational, so pinning them here makes
    // a wording change look like a broken menu.
    $section = collect($this->getJson('/api/menu/header')->json('data'))
        ->firstWhere('label', "Youth & Children's");

    expect($section)->not->toBeNull();

    expect(collect($section['children'])->pluck('url'))
        ->toContain('/youth-and-childrens/sbq', '/youth-and-childrens/jbq');
});

test('each quizzing programme is ordered to pair with its own ministry', function () {
    // SBQ is a youth programme and JBQ a children's one. The navbar renders
    // only two levels, so the pairing is carried by sort_order: Youth 10,
    // SBQ 20, Children's 30, JBQ 40 — each quiz immediately after its ministry
    // rather than both dumped at the end.
    //
    // Asserted on the sort_order VALUES rather than the rendered sequence: the
    // two department rows come from a seeder that does not run under
    // RefreshDatabase, so a sequence assertion would fail on their absence
    // rather than on the ordering being wrong. These values are what makes the
    // interleaving happen once the ministries are present.
    $sbq = MenuItem::where('url', '/youth-and-childrens/sbq')->first();
    $jbq = MenuItem::where('url', '/youth-and-childrens/jbq')->first();

    expect($sbq->sort_order)->toBe(20)
        ->and($jbq->sort_order)->toBe(40)
        // Same parent, or they are not in one section at all.
        ->and($sbq->parent_id)->toBe($jbq->parent_id)
        ->and($sbq->parent_id)->not->toBeNull();
});

test('no destination appears twice in the header menu', function () {
    // The Youth and Children's ministries were MOVED under the new section
    // rather than copied. Asserted as "no URL appears twice" rather than by
    // checking those two rows directly: the department rows come from a
    // seeder that does not run under RefreshDatabase, so a direct check
    // passes vacuously on an empty table. This invariant holds either way,
    // and catches any future duplicate too.
    $urls = MenuItem::where('location', 'header')
        ->where('url', '!=', '#')
        ->pluck('url');

    expect($urls->count())->toBe($urls->unique()->count());
});

test('top level items have no sort_order collisions', function () {
    // Find a Church and Apostolic Bible College were both 3, which made the
    // order depend on insertion rather than intent.
    $orders = MenuItem::where('location', 'header')
        ->whereNull('parent_id')
        ->pluck('sort_order');

    expect($orders->count())->toBe($orders->unique()->count());
});

test('the footer has menu rows to render', function () {
    // G1: the footer reads location=footer, and there were zero such rows.
    // Wiring it up without seeding first renders an empty footer.
    $labels = collect($this->getJson('/api/menu/footer')->json('data'))->pluck('label');

    expect($labels)->not->toBeEmpty()
        ->and($labels)->toContain('Regions', 'Gallery');
});

test('no social link points at twitter or x', function () {
    // Requirement 8, asserted against settings rather than markup: the point
    // is that there is no Twitter entry to render, not that a template hides
    // one.
    $links = collect(SiteSetting::current()->social_links ?? []);

    expect($links)->not->toBeEmpty();

    $links->each(function ($link) {
        expect(strtolower($link['platform'] ?? ''))->not->toBeIn(['twitter', 'x'])
            ->and(strtolower($link['url'] ?? ''))->not->toContain('twitter.com')
            ->and(strtolower($link['url'] ?? ''))->not->toContain('//x.com');
    });
});

test('the site settings endpoint publishes the social links', function () {
    $links = $this->getJson('/api/site-settings')->json('data.social_links');

    expect($links)->toBeArray()
        ->and(collect($links)->pluck('platform'))->toContain('facebook');
});
