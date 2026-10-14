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
use App\Filament\Resources\Regions\Pages\EditRegion;
use App\Filament\Resources\Departments\Pages\EditDepartment;
use App\Filament\Resources\Departments\Pages\ViewDepartment;
use App\Filament\Resources\Shared\GalleryItemsRelationManager;

/*
 * "Upload photos": many photos at once into a department's (or region's)
 * gallery, each becoming its own gallery item (owner request, 2026-10-15).
 */
beforeEach(function () {
    Storage::fake('public');
    Filament::setCurrentPanel('admin');

    $this->youth = Department::firstOrCreate(['slug' => 'youth'], ['name' => 'Youth Ministry']);
    $this->north = Region::firstOrCreate(['slug' => 'northern'], ['name' => 'Northern Region', 'sort_order' => 1]);
});

function bulkUploadAdmin(string $level, array $extra = []): User
{
    return User::create(array_merge([
        'name' => 'B', 'email' => $level.'-bulk-'.uniqid().'@x', 'password' => 'x',
        'role' => UserRole::ADMINISTRATOR, 'access_level' => AccessLevel::from($level),
    ], $extra));
}

function departmentGallery(string $page = EditDepartment::class): \Livewire\Features\SupportTesting\Testable
{
    return Livewire::test(GalleryItemsRelationManager::class, ['ownerRecord' => test()->youth, 'pageClass' => $page]);
}

function fakePhotos(int $count): array
{
    return array_map(fn ($i) => UploadedFile::fake()->image("photo-{$i}.jpg", 1600, 1200), range(1, $count));
}

function youthGalleryItems()
{
    return GalleryItem::ownedBy(test()->youth)->orderBy('sort_order')->get();
}

test('one upload makes one gallery item per photo, owned by the department', function () {
    $this->actingAs(bulkUploadAdmin('national'));

    departmentGallery()
        ->callTableAction('uploadPhotos', data: ['photos' => fakePhotos(3), 'caption' => 'Youth camp 2026', 'is_published' => true])
        ->assertHasNoTableActionErrors()
        ->assertNotified('3 photos added');

    $items = youthGalleryItems();

    expect($items)->toHaveCount(3)
        ->and($items->pluck('galleryable_type')->unique()->all())->toBe(['department'])
        ->and($items->pluck('title')->unique()->all())->toBe(['Youth camp 2026'])
        ->and($items->every(fn ($item) => str_starts_with($item->image_path, 'gallery/')))->toBeTrue();

    $items->each(fn ($item) => Storage::disk('public')->assertExists($item->image_path));
});

test('new photos are added after the existing ones, in upload order', function () {
    $this->actingAs(bulkUploadAdmin('national'));
    GalleryItem::create([
        'title' => 'Already there', 'image_path' => 'gallery/old.jpg',
        'galleryable_type' => 'department', 'galleryable_id' => $this->youth->id, 'sort_order' => 5,
    ]);

    departmentGallery()
        ->callTableAction('uploadPhotos', data: ['photos' => fakePhotos(3)])
        ->assertHasNoTableActionErrors();

    expect(youthGalleryItems()->pluck('sort_order')->all())->toBe([5, 6, 7, 8]);
});

test('without a caption the photos have no title', function () {
    $this->actingAs(bulkUploadAdmin('national'));

    departmentGallery()
        ->callTableAction('uploadPhotos', data: ['photos' => fakePhotos(2)])
        ->assertHasNoTableActionErrors()
        ->assertNotified('2 photos added');

    expect(youthGalleryItems()->pluck('title')->all())->toBe([null, null]);
});

test('photos uploaded as unpublished stay off the public gallery', function () {
    $this->actingAs(bulkUploadAdmin('national'));

    departmentGallery()
        ->callTableAction('uploadPhotos', data: ['photos' => fakePhotos(2), 'is_published' => false])
        ->assertHasNoTableActionErrors();

    expect(youthGalleryItems())->toHaveCount(2)
        ->and($this->getJson('/api/gallery?department=youth')->json('data'))->toBe([]);
});

test('more than 30 photos at once is refused, and nothing is added', function () {
    $this->actingAs(bulkUploadAdmin('national'));

    departmentGallery()
        ->callTableAction('uploadPhotos', data: ['photos' => fakePhotos(31)])
        ->assertHasTableActionErrors(['photos']);

    expect(youthGalleryItems())->toHaveCount(0);
});

test('an svg anywhere in the batch refuses the whole batch', function () {
    $this->actingAs(bulkUploadAdmin('national'));

    departmentGallery()
        ->callTableAction('uploadPhotos', data: ['photos' => [
            UploadedFile::fake()->image('fine.jpg'),
            UploadedFile::fake()->createWithContent('drawing.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>'),
        ]])
        ->assertHasTableActionErrors(['photos']);

    expect(youthGalleryItems())->toHaveCount(0);
});

test('uploading needs at least one photo', function () {
    $this->actingAs(bulkUploadAdmin('national'));

    departmentGallery()
        ->callTableAction('uploadPhotos', data: ['photos' => []])
        ->assertHasTableActionErrors(['photos']);
});

test('the upload is not offered on the read-only department view page', function () {
    $this->actingAs(bulkUploadAdmin('national'));

    departmentGallery(ViewDepartment::class)->assertTableActionHidden('uploadPhotos');
});

test('a regional user can upload to their own region gallery', function () {
    $this->actingAs(bulkUploadAdmin('regional', ['region_id' => $this->north->id]));

    Livewire::test(GalleryItemsRelationManager::class, ['ownerRecord' => $this->north, 'pageClass' => EditRegion::class])
        ->assertTableActionVisible('uploadPhotos')
        ->callTableAction('uploadPhotos', data: ['photos' => fakePhotos(2)])
        ->assertHasNoTableActionErrors();

    expect(GalleryItem::ownedBy($this->north)->count())->toBe(2)
        ->and(GalleryItem::ownedBy($this->youth)->count())->toBe(0);
});

test('a regional user cannot reach a department gallery at all', function () {
    $this->actingAs(bulkUploadAdmin('regional', ['region_id' => $this->north->id]))
        ->get("/admin/departments/{$this->youth->id}/edit")
        ->assertForbidden();
});

test('adding a single gallery item still works', function () {
    $this->actingAs(bulkUploadAdmin('national'));

    departmentGallery()
        ->callTableAction('create', data: ['title' => 'One photo', 'image_path' => UploadedFile::fake()->image('one.jpg')])
        ->assertHasNoTableActionErrors();

    expect(youthGalleryItems()->sole()->title)->toBe('One photo');
});

test('a batch is stored in file-name order, which is the order a phone took them', function () {
    // Not upload order: Filament stores uploads in the order they finish, and
    // the browser resizes each photo first, so a later photo can finish first.
    $this->actingAs(bulkUploadAdmin('national'));

    // Sizes identify the files after their names are replaced with random ones.
    departmentGallery()
        ->callTableAction('uploadPhotos', data: ['photos' => [
            UploadedFile::fake()->image('IMG_0413.jpg', 413, 100),
            UploadedFile::fake()->image('IMG_99.jpg', 99, 100),
            UploadedFile::fake()->image('IMG_0101.jpg', 101, 100),
        ]])
        ->assertHasNoTableActionErrors();

    $widths = youthGalleryItems()->map(fn ($item) => getimagesize(Storage::disk('public')->path($item->image_path))[0])->all();

    expect($widths)->toBe([99, 101, 413]);
});

test('stored files keep random names, never the uploaded ones', function () {
    $this->actingAs(bulkUploadAdmin('national'));

    departmentGallery()
        ->callTableAction('uploadPhotos', data: ['photos' => [UploadedFile::fake()->image('IMG_0001.jpg')]])
        ->assertHasNoTableActionErrors();

    expect(youthGalleryItems()->sole()->image_path)->not->toContain('IMG_0001');
});
