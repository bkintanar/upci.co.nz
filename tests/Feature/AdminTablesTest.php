<?php

use App\Models\User;
use App\Models\Event;
use App\Models\Church;
use App\Models\Region;
use Livewire\Livewire;
use App\Enums\UserRole;
use App\Models\AGSUpdate;
use App\Enums\AccessLevel;
use App\Models\Department;
use App\Models\GalleryItem;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Resources\Events\Pages\ListEvents;
use App\Filament\Resources\Churches\Pages\ListChurches;
use App\Filament\Resources\AGSUpdates\Pages\ListAGSUpdates;
use App\Filament\Resources\Departments\Pages\EditDepartment;
use App\Filament\Resources\Departments\Pages\ListDepartments;
use App\Filament\Resources\GalleryItems\Pages\ListGalleryItems;
use App\Filament\Resources\Departments\RelationManagers\EventsRelationManager;

/**
 * Admin list tables that silently rendered the wrong thing.
 *
 * Filament injects closure parameters by NAME, so `fn ($s)` always received
 * null and every is_published badge read "Draft"; `fn ($record)` in a column's
 * visible() has no row to bind to, so those columns never showed; and sorting a
 * MorphTo column threw a SQL error.
 */
beforeEach(function () {
    $this->actingAs(User::create([
        'name' => 'N', 'email' => 'tables-national@x', 'password' => 'x',
        'role' => UserRole::ADMINISTRATOR,
        'access_level' => AccessLevel::NATIONAL,
    ]));
    Filament::setCurrentPanel('admin');
});

function publishedBadgeIs(string $label, string $color): Closure
{
    return fn (TextColumn $column) => $column->getColor($label === 'Published') === $color;
}

test('the events table badges a published event as Published', function () {
    $published = Event::create(['name' => 'Camp', 'slug' => 'camp', 'start_date' => now(), 'is_published' => true]);
    $draft = Event::create(['name' => 'Rally', 'slug' => 'rally', 'start_date' => now(), 'is_published' => false]);

    Livewire::test(ListEvents::class)
        ->assertTableColumnFormattedStateSet('is_published', 'Published', $published)
        ->assertTableColumnFormattedStateSet('is_published', 'Draft', $draft)
        ->assertTableColumnExists('is_published', publishedBadgeIs('Published', 'success'), $published)
        ->assertTableColumnExists('is_published', publishedBadgeIs('Draft', 'gray'), $draft);
});

test('the departments table badges a published department as Published', function () {
    $published = Department::create(['name' => 'Table Youth', 'slug' => 'table-youth', 'is_published' => true]);
    $draft = Department::create(['name' => 'Table Ladies', 'slug' => 'table-ladies', 'is_published' => false]);

    Livewire::test(ListDepartments::class)
        ->assertTableColumnFormattedStateSet('is_published', 'Published', $published)
        ->assertTableColumnFormattedStateSet('is_published', 'Draft', $draft)
        ->assertTableColumnExists('is_published', publishedBadgeIs('Published', 'success'), $published);
});

test('the department events relation manager badges a published event as Published', function () {
    $department = Department::create(['name' => 'Table Youth', 'slug' => 'table-youth', 'is_published' => true]);
    $published = Event::create([
        'name' => 'Camp', 'slug' => 'camp', 'start_date' => now(), 'is_published' => true,
        'department_id' => $department->id,
    ]);
    $draft = Event::create([
        'name' => 'Rally', 'slug' => 'rally', 'start_date' => now(), 'is_published' => false,
        'department_id' => $department->id,
    ]);

    Livewire::test(EventsRelationManager::class, ['ownerRecord' => $department, 'pageClass' => EditDepartment::class])
        ->assertTableColumnFormattedStateSet('is_published', 'Published', $published)
        ->assertTableColumnFormattedStateSet('is_published', 'Draft', $draft)
        ->assertTableColumnExists('is_published', publishedBadgeIs('Published', 'success'), $published);
});

test('the AGS updates table badges a published update as Published', function () {
    $published = AGSUpdate::create(['title' => 'Q1', 'content' => 'x', 'is_published' => true, 'published_at' => now()]);
    $draft = AGSUpdate::create(['title' => 'Q2', 'content' => 'x', 'is_published' => false]);

    Livewire::test(ListAGSUpdates::class)
        ->assertTableColumnFormattedStateSet('is_published', 'Published', $published)
        ->assertTableColumnFormattedStateSet('is_published', 'Draft', $draft)
        ->assertTableColumnExists('is_published', publishedBadgeIs('Published', 'success'), $published);
});

test('the churches table shows its city, suburb, email and phone columns', function () {
    $region = Region::firstOrCreate(['slug' => 'northern'], ['name' => 'Northern Region', 'sort_order' => 1]);
    $church = Church::create([
        'name' => 'Table Church', 'region_id' => $region->id, 'is_active' => true,
        'city' => 'Auckland', 'suburb' => 'Ponsonby',
        'email' => 'office@table.test', 'phone' => '09 555 0100',
    ]);

    Livewire::test(ListChurches::class)
        ->assertTableColumnVisible('city')
        ->assertTableColumnVisible('suburb')
        ->assertTableColumnVisible('email')
        ->assertTableColumnVisible('phone')
        ->assertCanSeeTableRecords([$church])
        ->assertSee('Auckland')
        ->assertSee('Ponsonby')
        ->assertSee('office@table.test');
});

test('the churches table has no column for the dropped state field', function () {
    Livewire::test(ListChurches::class)
        ->assertTableColumnDoesNotExist('state');
});

test('the gallery table belongs-to column is not sortable, so sorting it cannot throw', function () {
    $youth = Department::create(['name' => 'Table Youth', 'slug' => 'table-youth', 'is_published' => true]);
    GalleryItem::create([
        'title' => 'Youth camp', 'image_path' => 'gallery/a.jpg', 'is_published' => true,
        'galleryable_type' => 'department', 'galleryable_id' => $youth->id,
    ]);

    Livewire::test(ListGalleryItems::class)
        ->assertTableColumnExists('galleryable.name', fn (TextColumn $column) => ! $column->isSortable())
        ->call('sortTable', 'galleryable.name')
        ->assertSuccessful()
        ->assertSee('Youth camp');
});
