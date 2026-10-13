<?php

use App\Models\User;
use App\Models\Church;
use App\Models\Region;
use Livewire\Livewire;
use App\Enums\UserRole;
use App\Enums\AccessLevel;
use Filament\Facades\Filament;
use App\Filament\Resources\Churches\Pages\EditChurch;

/*
 * Regression: after the Filament 4.0 → 4.15 upgrade, the church form's
 * is_active / is_featured Selects (option keys 1/0) dropped a loaded boolean
 * because `false` is not one of their keys, and saving wrote NULL into the
 * NOT NULL is_featured column. Saving a church must keep both values exactly.
 */
beforeEach(function () {
    Filament::setCurrentPanel('admin');
    $this->region = Region::create(['name' => 'Bool North', 'slug' => 'bool-north', 'sort_order' => 1]);
});

function churchBooleanStateUser(): User
{
    return User::create([
        'name' => 'T',
        'email' => 'church-boolean-state@x',
        'password' => 'x',
        'role' => UserRole::ADMINISTRATOR,
        'access_level' => AccessLevel::NATIONAL,
    ]);
}

test('saving a church keeps its active and featured values', function (bool $active, bool $featured) {
    $church = Church::create(['name' => 'Bool Church', 'region_id' => $this->region->id, 'is_active' => $active, 'is_featured' => $featured]);

    $this->actingAs(churchBooleanStateUser());

    Livewire::test(EditChurch::class, ['record' => $church->fresh()->getRouteKey()])
        ->set('data.name', 'Bool Church Renamed')
        ->call('save')
        ->assertHasNoFormErrors();

    $church->refresh();

    expect($church->name)->toBe('Bool Church Renamed')
        ->and($church->is_active)->toBe($active)
        ->and($church->is_featured)->toBe($featured);
})->with([
    'active, not featured' => [true, false],
    'active, featured' => [true, true],
    'inactive, featured' => [false, true],
    'inactive, not featured' => [false, false],
]);

test('a church loaded without an is_featured value saves as not featured', function () {
    $church = Church::create(['name' => 'Unset', 'region_id' => $this->region->id, 'is_active' => true]);

    $this->actingAs(churchBooleanStateUser());

    Livewire::test(EditChurch::class, ['record' => $church->getRouteKey()])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($church->refresh()->is_featured)->toBeFalse();
});
