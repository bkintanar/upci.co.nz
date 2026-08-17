<?php

use App\Models\Region;
use App\Models\Department;
use App\Models\GalleryItem;

beforeEach(function () {
    $this->youth = Department::firstOrCreate(['slug' => 'youth'], ['name' => 'Youth Ministry']);
    $this->north = Region::firstOrCreate(['slug' => 'northern'], ['name' => 'Northern Region', 'sort_order' => 1]);

    $this->deptItem = GalleryItem::create([
        'title' => 'Youth camp', 'image_path' => 'gallery/a.jpg', 'is_published' => true,
        'galleryable_type' => 'department', 'galleryable_id' => $this->youth->id,
    ]);
    $this->regionItem = GalleryItem::create([
        'title' => 'Northern rally', 'image_path' => 'gallery/b.jpg', 'is_published' => true,
        'galleryable_type' => 'region', 'galleryable_id' => $this->north->id,
    ]);
    $this->generalItem = GalleryItem::create([
        'title' => 'Anniversary', 'image_path' => 'gallery/c.jpg', 'is_published' => true,
    ]);
});

test('one model serves department, region and general galleries', function () {
    // Requirement 2's actual ask: not three implementations.
    expect(GalleryItem::ownedBy($this->youth)->pluck('title')->all())->toBe(['Youth camp'])
        ->and(GalleryItem::ownedBy($this->north)->pluck('title')->all())->toBe(['Northern rally'])
        ->and(GalleryItem::general()->pluck('title')->all())->toBe(['Anniversary']);
});

test('a department gallery excludes region and general items', function () {
    $titles = collect($this->getJson('/api/gallery?department=youth')->json('data'))->pluck('title');

    expect($titles)->toContain('Youth camp')
        ->and($titles)->not->toContain('Northern rally')
        ->and($titles)->not->toContain('Anniversary');
});

test('a region gallery excludes department and general items', function () {
    $titles = collect($this->getJson('/api/gallery?region=northern')->json('data'))->pluck('title');

    expect($titles)->toContain('Northern rally')
        ->and($titles)->not->toContain('Youth camp')
        ->and($titles)->not->toContain('Anniversary');
});

test('the general gallery is items owned by nobody', function () {
    $titles = collect($this->getJson('/api/gallery?owner=general')->json('data'))->pluck('title');

    expect($titles)->toContain('Anniversary')
        ->and($titles)->not->toContain('Youth camp');
});

test('unpublished items are withheld from every gallery', function () {
    // The table had no visibility column at all, so anything saved went live.
    GalleryItem::create([
        'title' => 'Draft shot', 'image_path' => 'gallery/d.jpg', 'is_published' => false,
        'galleryable_type' => 'department', 'galleryable_id' => $this->youth->id,
    ]);

    $all = collect($this->getJson('/api/gallery')->json('data'))->pluck('title');
    $dept = collect($this->getJson('/api/gallery?department=youth')->json('data'))->pluck('title');

    expect($all)->not->toContain('Draft shot')
        ->and($dept)->not->toContain('Draft shot');
});

test('an unknown owner is rejected rather than returning an empty gallery', function () {
    $this->getJson('/api/gallery?department=nope')->assertStatus(422);
    $this->getJson('/api/gallery?region=nope')->assertStatus(422);
});

test('the payload always names an owner, general included', function () {
    // The frontend switches on owner.type; a missing key and a null are
    // different failures in JS.
    $items = collect($this->getJson('/api/gallery')->json('data'));

    expect($items->firstWhere('title', 'Youth camp')['owner']['type'])->toBe('department')
        ->and($items->firstWhere('title', 'Northern rally')['owner']['type'])->toBe('region')
        ->and($items->firstWhere('title', 'Anniversary')['owner']['type'])->toBe('general');
});

test('ownership is stored as a morph alias, not a class path', function () {
    // enforceMorphMap keeps rows portable across class moves, and makes an
    // unregistered owner throw at write time instead of writing a class name
    // that later resolves to nothing.
    expect($this->deptItem->fresh()->galleryable_type)->toBe('department')
        ->and($this->regionItem->fresh()->galleryable_type)->toBe('region');
});

test('an unregistered model cannot be made a gallery owner', function () {
    expect(fn () => (new App\Models\Church)->getMorphClass())
        ->toThrow(Illuminate\Database\ClassMorphViolationException::class);
});

test('the morph relation resolves back to the owning model', function () {
    expect($this->deptItem->galleryable)->toBeInstanceOf(Department::class)
        ->and($this->deptItem->galleryable->slug)->toBe('youth')
        ->and($this->generalItem->galleryable)->toBeNull();
});
