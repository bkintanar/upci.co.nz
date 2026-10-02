<?php

use App\Models\MenuItem;

/*
 * Regression for menu-inactive-children-leak: active() only constrained the
 * top level, so a child or grandchild switched off in the admin still
 * reached the public menu.
 */

function menuItem(array $attributes): MenuItem
{
    return MenuItem::create(array_merge([
        'url' => '/'.str($attributes['label'])->slug(),
        'location' => 'header',
        'sort_order' => 999,
        'is_active' => true,
    ], $attributes));
}

/**
 * Every label in a menu response, at any depth.
 */
function menuLabels(array $items): array
{
    return collect($items)
        ->flatMap(fn (array $item) => [$item['label'], ...menuLabels($item['children'] ?? [])])
        ->all();
}

beforeEach(function () {
    $this->parent = menuItem(['label' => 'Audit Parent']);
    $this->activeChild = menuItem(['label' => 'Audit Active Child', 'parent_id' => $this->parent->id]);
    $this->inactiveChild = menuItem(['label' => 'Audit Inactive Child', 'parent_id' => $this->parent->id, 'is_active' => false]);
    $this->activeGrandchild = menuItem(['label' => 'Audit Active Grandchild', 'parent_id' => $this->activeChild->id]);
    $this->inactiveGrandchild = menuItem(['label' => 'Audit Inactive Grandchild', 'parent_id' => $this->activeChild->id, 'is_active' => false]);
});

test('an inactive child is left out of the header menu', function () {
    $labels = menuLabels($this->getJson('/api/menu/header')->assertOk()->json('data'));

    expect($labels)->toContain('Audit Parent', 'Audit Active Child')
        ->and($labels)->not->toContain('Audit Inactive Child');
});

test('an inactive grandchild is left out of the header menu', function () {
    $labels = menuLabels($this->getJson('/api/menu/header')->assertOk()->json('data'));

    expect($labels)->toContain('Audit Active Grandchild')
        ->and($labels)->not->toContain('Audit Inactive Grandchild');
});

test('the children of an inactive child are left out with it', function () {
    menuItem(['label' => 'Audit Orphaned Grandchild', 'parent_id' => $this->inactiveChild->id]);

    $labels = menuLabels($this->getJson('/api/menu/header')->json('data'));

    expect($labels)->not->toContain('Audit Orphaned Grandchild');
});

test('children are a list, not an object keyed by the surviving indexes', function () {
    // Filtering a collection keeps its keys; without values() the first
    // child dropping would turn the JSON array into an object.
    $this->inactiveChild->update(['sort_order' => -1]);

    $parent = collect($this->getJson('/api/menu/header')->json('data'))->firstWhere('label', 'Audit Parent');

    expect(array_is_list($parent['children']))->toBeTrue()
        ->and(collect($parent['children'])->pluck('label')->all())->toBe(['Audit Active Child']);
});

test('the footer menu filters inactive children too', function () {
    $parent = menuItem(['label' => 'Audit Footer Parent', 'location' => 'footer']);
    menuItem(['label' => 'Audit Footer Active', 'location' => 'footer', 'parent_id' => $parent->id]);
    menuItem(['label' => 'Audit Footer Inactive', 'location' => 'footer', 'parent_id' => $parent->id, 'is_active' => false]);

    $labels = menuLabels($this->getJson('/api/menu/footer')->assertOk()->json('data'));

    expect($labels)->toContain('Audit Footer Parent', 'Audit Footer Active')
        ->and($labels)->not->toContain('Audit Footer Inactive');
});

test('an inactive top-level item is still left out', function () {
    $this->parent->update(['is_active' => false]);

    $labels = menuLabels($this->getJson('/api/menu/header')->json('data'));

    expect($labels)->not->toContain('Audit Parent', 'Audit Active Child');
});
