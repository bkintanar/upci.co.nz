<?php

use App\Models\User;
use App\Models\Region;
use Livewire\Livewire;
use App\Enums\UserRole;
use App\Enums\AccessLevel;
use App\Models\Department;
use App\Models\GalleryItem;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Filament\Resources\GalleryItems\Pages\ListGalleryItems;
use App\Filament\Resources\GalleryItems\Pages\CreateGalleryItem;

/*
 * "For the general gallery cms should be able to add photos for the general
 * gallery" (owner, 2026-10-17): the Gallery list's own "Upload photos", the
 * same bulk upload departments and regions have, into the gallery nobody
 * owns.
 */
beforeEach(function () {
    Storage::fake('public');
    Filament::setCurrentPanel('admin');

    $this->youth = Department::firstOrCreate(['slug' => 'youth'], ['name' => 'Youth Ministry']);
    $this->north = Region::firstOrCreate(['slug' => 'northern'], ['name' => 'Northern Region', 'sort_order' => 1]);
});

function generalGalleryAdmin(string $level, array $extra = []): User
{
    return User::create(array_merge([
        'name' => 'G', 'email' => $level.'-general-'.uniqid().'@x', 'password' => 'x',
        'role' => UserRole::ADMINISTRATOR, 'access_level' => AccessLevel::from($level),
    ], $extra));
}

function generalGalleryPhotos(int $count): array
{
    return array_map(fn ($i) => UploadedFile::fake()->image("general-{$i}.jpg", 1600, 1200), range(1, $count));
}

test('a national admin uploads a batch straight into the general gallery', function () {
    $this->actingAs(generalGalleryAdmin('national'));

    Livewire::test(ListGalleryItems::class)
        ->assertActionVisible('uploadPhotos')
        ->callAction('uploadPhotos', data: ['photos' => generalGalleryPhotos(3), 'caption' => 'Conference 2026', 'is_published' => true])
        ->assertHasNoActionErrors()
        ->assertNotified('3 photos added');

    $items = GalleryItem::general()->orderBy('sort_order')->get();

    expect($items)->toHaveCount(3)
        ->and($items->pluck('galleryable_id')->unique()->all())->toBe([null])
        ->and($items->pluck('title')->unique()->all())->toBe(['Conference 2026']);

    $items->each(fn ($item) => Storage::disk('public')->assertExists($item->image_path));

    $general = collect($this->getJson('/api/gallery?owner=general')->json('data'))->pluck('title');
    expect($general)->toHaveCount(3)
        ->and($this->getJson('/api/gallery?department=youth')->json('data'))->toBe([]);
});

test('general uploads go after the general gallery\'s last photo, whatever departments hold', function () {
    $this->actingAs(generalGalleryAdmin('national'));
    GalleryItem::create(['title' => 'General already there', 'image_path' => 'gallery/a.jpg', 'sort_order' => 4]);
    GalleryItem::create([
        'title' => 'Youth photo', 'image_path' => 'gallery/b.jpg', 'sort_order' => 40,
        'galleryable_type' => 'department', 'galleryable_id' => $this->youth->id,
    ]);

    Livewire::test(ListGalleryItems::class)
        ->callAction('uploadPhotos', data: ['photos' => generalGalleryPhotos(2)])
        ->assertHasNoActionErrors();

    expect(GalleryItem::general()->orderBy('sort_order')->pluck('sort_order')->all())->toBe([4, 5, 6]);
});

test('a general batch is stored in file-name order', function () {
    $this->actingAs(generalGalleryAdmin('national'));

    Livewire::test(ListGalleryItems::class)
        ->callAction('uploadPhotos', data: ['photos' => [
            UploadedFile::fake()->image('IMG_0413.jpg', 413, 100),
            UploadedFile::fake()->image('IMG_99.jpg', 99, 100),
            UploadedFile::fake()->image('IMG_0101.jpg', 101, 100),
        ]])
        ->assertHasNoActionErrors();

    $widths = GalleryItem::general()->orderBy('sort_order')->get()
        ->map(fn ($item) => getimagesize(Storage::disk('public')->path($item->image_path))[0])->all();

    expect($widths)->toBe([99, 101, 413]);
});

test('photos uploaded as unpublished stay off the general gallery', function () {
    $this->actingAs(generalGalleryAdmin('national'));

    Livewire::test(ListGalleryItems::class)
        ->callAction('uploadPhotos', data: ['photos' => generalGalleryPhotos(2), 'is_published' => false])
        ->assertHasNoActionErrors();

    expect(GalleryItem::general()->count())->toBe(2)
        ->and($this->getJson('/api/gallery?owner=general')->json('data'))->toBe([]);
});

test('more than 30 photos is refused here too', function () {
    $this->actingAs(generalGalleryAdmin('national'));

    Livewire::test(ListGalleryItems::class)
        ->callAction('uploadPhotos', data: ['photos' => generalGalleryPhotos(31)])
        ->assertHasActionErrors(['photos']);

    expect(GalleryItem::count())->toBe(0);
});

test('a regional admin does not get the general gallery upload', function () {
    // They see the Gallery list, limited to their region's photos, but the
    // general gallery is national.
    $this->actingAs(generalGalleryAdmin('regional', ['region_id' => $this->north->id]));

    Livewire::test(ListGalleryItems::class)->assertActionHidden('uploadPhotos');
});

test('the single-item form says that leaving the owner blank means the general gallery', function () {
    $this->actingAs(generalGalleryAdmin('national'));

    Livewire::test(CreateGalleryItem::class)
        ->assertSee('Leave "Belongs to" blank to put the photo in the general gallery');
});
