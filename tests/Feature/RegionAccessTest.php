<?php

use App\Models\User;
use App\Models\Church;
use App\Models\Region;
use App\Enums\UserRole;
use App\Enums\AccessLevel;
use App\Filament\Resources\Regions\RegionResource;

beforeEach(function () {
    $this->north = Region::firstOrCreate(['slug' => 'northern'], ['name' => 'Northern Region', 'sort_order' => 1]);
    $this->south = Region::firstOrCreate(['slug' => 'southern'], ['name' => 'Southern Region', 'sort_order' => 3]);
});

function regionUser(AccessLevel $level, ?int $regionId = null): User
{
    return User::create([
        'name' => 'U',
        'email' => 'u'.uniqid().'@x',
        'password' => 'x',
        'role' => UserRole::SENIOR_PASTOR,
        'access_level' => $level,
        'region_id' => $regionId,
    ]);
}

test('a regional user can edit their own region', function () {
    $user = regionUser(AccessLevel::REGIONAL, $this->north->id);

    expect($user->can('update', $this->north))->toBeTrue();
});

test('a regional user cannot edit another region', function () {
    $user = regionUser(AccessLevel::REGIONAL, $this->north->id);

    expect($user->can('update', $this->south))->toBeFalse();
});

test('a local user cannot edit any region', function () {
    // A local is scoped to a church, and a church does not own the region it
    // sits in. region_id may even be set on them; it still must not grant this.
    $user = regionUser(AccessLevel::LOCAL, $this->north->id);

    expect($user->can('update', $this->north))->toBeFalse();
});

test('creating and deleting regions is national only', function () {
    $regional = regionUser(AccessLevel::REGIONAL, $this->north->id);
    $national = regionUser(AccessLevel::NATIONAL);

    expect($regional->can('create', Region::class))->toBeFalse()
        ->and($regional->can('delete', $this->north))->toBeFalse()
        ->and($national->can('create', Region::class))->toBeTrue()
        ->and($national->can('delete', $this->north))->toBeTrue();
});

test('the region list is scoped, not just gated', function () {
    // The policy stops a regional user OPENING another region. This asserts
    // the list page does not even name one — otherwise the table renders all
    // three rows and only fails on click.
    $user = regionUser(AccessLevel::REGIONAL, $this->north->id);
    $this->actingAs($user);

    $visible = RegionResource::getEloquentQuery()->pluck('id')->all();

    expect($visible)->toBe([$this->north->id]);
});

test('the region resource exposes no create route', function () {
    // Regions are a fixed taxonomy. A registered create page would be
    // reachable by URL even with the policy denying the action.
    expect(array_keys(RegionResource::getPages()))->not->toContain('create');
});

test('an unpublished region with churches still appears in the locator filter', function () {
    // Guards the same defect class as filtering the church list on
    // has-coordinates: is_published describes the LANDING PAGE, so using it
    // alone here would hide a live region's churches from the filter.
    Church::create(['name' => 'Live one', 'region_id' => $this->north->id, 'is_active' => true]);
    $this->north->update(['is_published' => false]);

    $slugs = collect($this->getJson('/api/churches-organizational-regions')->json('data'))->pluck('slug');

    expect($slugs)->toContain('northern');
});

test('an unpublished region with no churches is withheld', function () {
    $this->south->update(['is_published' => false]);

    $slugs = collect($this->getJson('/api/churches-organizational-regions')->json('data'))->pluck('slug');

    expect($slugs)->not->toContain('southern');
});

test('region content fields are fillable', function () {
    // A column missing from $fillable makes update() a silent no-op — the
    // admin reports success and nothing persists.
    $this->north->update([
        'intro' => 'Welcome to the north.',
        'presbyter_name' => 'Rev. Placeholder',
        'logo_path' => 'region-logos/north.png',
    ]);

    expect($this->north->fresh())
        ->intro->toBe('Welcome to the north.')
        ->presbyter_name->toBe('Rev. Placeholder')
        ->logo_path->toBe('region-logos/north.png');
});

test('the region show payload always carries churches, events and gallery as arrays', function () {
    // Region.vue reads region.churches.length directly. A null or a missing
    // key throws in the template and blanks the whole page, so the shape is
    // part of the contract rather than an implementation detail.
    $data = $this->getJson('/api/regions/northern')->json('data');

    expect($data)->toHaveKeys(['slug', 'name', 'logo_url', 'intro', 'presbyter_name', 'churches', 'events', 'gallery'])
        ->and($data['churches'])->toBeArray()
        ->and($data['events'])->toBeArray()
        ->and($data['gallery'])->toBeArray();
});

test('the region index reports a church count', function () {
    App\Models\Church::create(['name' => 'Counted', 'region_id' => $this->north->id, 'is_active' => true]);
    App\Models\Church::create(['name' => 'Inactive', 'region_id' => $this->north->id, 'is_active' => false]);

    $row = collect($this->getJson('/api/regions')->json('data'))->firstWhere('slug', 'northern');

    // Inactive churches are excluded from the count, matching the list.
    expect($row['churches_count'])->toBe(1);
});

test('an unpublished region is not reachable through the public region endpoints', function () {
    $this->north->update(['is_published' => false]);

    $slugs = collect($this->getJson('/api/regions')->json('data'))->pluck('slug');

    expect($slugs)->not->toContain('northern');
    $this->getJson('/api/regions/northern')->assertStatus(404);
});

test('the region page shows only its own regional events', function () {
    $national = App\Models\Event::create([
        'name' => 'National one', 'slug' => 'nat-one', 'start_date' => '2026-10-01',
        'is_published' => true, 'scope' => App\Enums\EventScope::NATIONAL,
    ]);
    App\Models\Event::create([
        'name' => 'Northern one', 'slug' => 'north-one', 'start_date' => '2026-10-02',
        'is_published' => true, 'scope' => App\Enums\EventScope::REGIONAL, 'region_id' => $this->north->id,
    ]);

    $names = collect($this->getJson('/api/regions/northern')->json('data.events'))->pluck('name');

    expect($names)->toContain('Northern one')
        ->and($names)->not->toContain('National one');
});
