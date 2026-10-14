<?php

use App\Models\User;
use App\Models\Event;
use App\Models\Church;
use App\Models\Region;
use App\Enums\UserRole;
use App\Enums\EventScope;
use App\Enums\AccessLevel;
use App\Models\Department;
use App\Models\GalleryItem;
use Filament\Facades\Filament;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Events\EventResource;
use App\Filament\Resources\GalleryItems\GalleryItemResource;

/*
 * "Regional pastors should only see their data nothing more" (owner,
 * 2026-10-15). A regional user saw every region's events, every department
 * and every gallery photo read-only, and listed national board members who
 * attend a church in their region. Board members who are also regional
 * pastors hold national access and keep the full view; that is deliberate
 * ("some regional pastors are members of the national board").
 */
beforeEach(function () {
    Filament::setCurrentPanel('admin');

    $this->north = Region::firstOrCreate(['slug' => 'northern'], ['name' => 'Northern Region', 'sort_order' => 1]);
    $this->south = Region::firstOrCreate(['slug' => 'southern'], ['name' => 'Southern Region', 'sort_order' => 3]);
    $this->northChurch = Church::create(['name' => 'North Church', 'region_id' => $this->north->id, 'is_active' => true]);

    $this->regional = User::create([
        'name' => 'R', 'email' => 'regional-own-data@x', 'password' => 'x',
        'role' => UserRole::REGIONAL_PRESBYTER, 'access_level' => AccessLevel::REGIONAL,
        'region_id' => $this->north->id,
    ]);
});

function ownDataEvent(array $attrs): Event
{
    return Event::create(array_merge([
        'name' => 'An event', 'slug' => 'event-'.uniqid(), 'start_date' => '2026-11-01', 'is_published' => true,
        'scope' => EventScope::REGIONAL,
    ], $attrs));
}

test('a regional user lists only their own region\'s events', function () {
    $own = ownDataEvent(['name' => 'Northern rally', 'region_id' => $this->north->id]);
    $other = ownDataEvent(['name' => 'Southern rally', 'region_id' => $this->south->id]);
    $national = ownDataEvent(['name' => 'General Conference', 'scope' => EventScope::NATIONAL]);

    $this->actingAs($this->regional);

    expect(EventResource::getEloquentQuery()->pluck('id')->all())->toBe([$own->id])
        ->and($this->regional->can('view', $own))->toBeTrue()
        ->and($this->regional->can('view', $other))->toBeFalse()
        ->and($this->regional->can('view', $national))->toBeFalse()
        ->and($this->regional->can('update', $own))->toBeFalse();
});

test('a regional user with no region sees no events at all', function () {
    $orphan = User::create([
        'name' => 'O', 'email' => 'regional-no-region@x', 'password' => 'x',
        'role' => UserRole::REGIONAL_PRESBYTER, 'access_level' => AccessLevel::REGIONAL,
    ]);
    $regionless = ownDataEvent(['name' => 'No region', 'scope' => EventScope::NATIONAL]);

    $this->actingAs($orphan);

    expect(EventResource::getEloquentQuery()->count())->toBe(0)
        ->and($orphan->can('view', $regionless))->toBeFalse();
});

test('a national user still sees every event', function () {
    ownDataEvent(['region_id' => $this->north->id]);
    ownDataEvent(['region_id' => $this->south->id]);
    ownDataEvent(['scope' => EventScope::NATIONAL]);

    $this->actingAs(User::create([
        'name' => 'N', 'email' => 'national-own-data@x', 'password' => 'x',
        'role' => UserRole::EXECUTIVE_BOARD, 'access_level' => AccessLevel::NATIONAL,
    ]));

    expect(EventResource::getEloquentQuery()->count())->toBe(Event::count());
});

test('departments are national only', function () {
    $department = Department::firstOrCreate(['slug' => 'mens'], ['name' => "National Men's Department"]);
    $local = User::create([
        'name' => 'L', 'email' => 'local-own-data@x', 'password' => 'x',
        'role' => UserRole::PASTOR, 'access_level' => AccessLevel::LOCAL, 'church_id' => $this->northChurch->id,
    ]);
    $national = User::create([
        'name' => 'N', 'email' => 'national-dept-own-data@x', 'password' => 'x',
        'role' => UserRole::EXECUTIVE_BOARD, 'access_level' => AccessLevel::NATIONAL,
    ]);

    expect($this->regional->can('viewAny', Department::class))->toBeFalse()
        ->and($this->regional->can('view', $department))->toBeFalse()
        ->and($local->can('viewAny', Department::class))->toBeFalse()
        ->and($national->can('viewAny', Department::class))->toBeTrue();

    $this->actingAs($this->regional)->get('/admin/departments')->assertForbidden();
});

test('a regional user\'s gallery list holds only their own region\'s photos', function () {
    $own = GalleryItem::create(['title' => 'Ours', 'image_path' => 'gallery/a.jpg', 'galleryable_type' => 'region', 'galleryable_id' => $this->north->id]);
    GalleryItem::create(['title' => 'Theirs', 'image_path' => 'gallery/b.jpg', 'galleryable_type' => 'region', 'galleryable_id' => $this->south->id]);
    GalleryItem::create(['title' => 'General', 'image_path' => 'gallery/c.jpg']);
    $department = Department::firstOrCreate(['slug' => 'youth'], ['name' => 'Youth Ministry']);
    GalleryItem::create(['title' => 'Department', 'image_path' => 'gallery/d.jpg', 'galleryable_type' => 'department', 'galleryable_id' => $department->id]);

    $this->actingAs($this->regional);

    expect(GalleryItemResource::getEloquentQuery()->pluck('id')->all())->toBe([$own->id]);
});

test('a regional user lists themselves and lower-ranked people in their region, not board members', function () {
    $localPastor = User::create([
        'name' => 'Local', 'email' => 'local-pastor-own-data@x', 'password' => 'x',
        'role' => UserRole::PASTOR, 'access_level' => AccessLevel::LOCAL, 'church_id' => $this->northChurch->id,
    ]);
    $member = User::create([
        'name' => 'Member', 'email' => 'member-own-data@x', 'password' => 'x',
        'role' => UserRole::USHER, 'church_id' => $this->northChurch->id,
    ]);
    $boardMember = User::create([
        'name' => 'Board', 'email' => 'board-own-data@x', 'password' => 'x',
        'role' => UserRole::REGIONAL_PRESBYTER, 'access_level' => AccessLevel::NATIONAL, 'church_id' => $this->northChurch->id,
    ]);
    $peer = User::create([
        'name' => 'Peer', 'email' => 'peer-own-data@x', 'password' => 'x',
        'role' => UserRole::REGIONAL_PRESBYTER, 'access_level' => AccessLevel::REGIONAL,
        'region_id' => $this->north->id, 'church_id' => $this->northChurch->id,
    ]);

    $this->actingAs($this->regional);
    $ids = UserResource::getEloquentQuery()->pluck('id')->all();

    expect($ids)->toContain($this->regional->id, $localPastor->id, $member->id)
        ->not->toContain($boardMember->id)
        ->not->toContain($peer->id);
});
