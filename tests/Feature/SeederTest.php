<?php

use App\Models\User;
use App\Models\Church;
use App\Models\Region;
use App\Enums\UserRole;
use App\Enums\AccessLevel;
use Filament\Facades\Filament;
use Database\Seeders\ChurchSeeder;
use Database\Seeders\DatabaseSeeder;

/*
 * Seeders run on fresh or dev installs only. These guard the two ways they
 * broke: ChurchSeeder wrote the dropped `state` column, and DatabaseSeeder
 * created an admin with a NULL access_level that the panel gate rejects.
 */

test('ChurchSeeder runs against the current churches schema', function () {
    $this->seed(ChurchSeeder::class);

    expect(Church::count())->toBeGreaterThan(0);
});

test('ChurchSeeder writes service_times in the {service_type, time, days} shape', function () {
    $this->seed(ChurchSeeder::class);

    Church::all()->each(function (Church $church) {
        expect($church->service_times)->toBeArray()->not->toBeEmpty();

        foreach ($church->service_times as $service) {
            expect($service)->toHaveKeys(['service_type', 'time', 'days'])
                ->and($service)->not->toHaveKey('day')
                ->and($service['days'])->toBeArray()->not->toBeEmpty();

            foreach ($service['days'] as $day) {
                expect($day)->toBeIn(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']);
            }
        }
    });
});

test('ChurchSeeder assigns each church to an organisational region by slug', function () {
    $this->seed(ChurchSeeder::class);

    $wellington = Church::where('name', 'Church Triumphant Wellington')->firstOrFail();
    $auckland = Church::where('name', 'Auckland UPCI')->firstOrFail();

    expect($wellington->suburb)->toBe('Churton Park')
        ->and($wellington->organizationalRegion?->slug)->toBe('southern')
        ->and($auckland->organizationalRegion?->slug)->toBe('northern')
        ->and(Church::whereNull('region_id')->count())->toBe(0);
});

test('ChurchSeeder seeds churches the public locator API returns', function () {
    $this->seed(ChurchSeeder::class);

    $this->getJson('/api/churches?service_day=sunday')
        ->assertOk()
        ->assertJsonFragment(['name' => 'Church Triumphant Wellington']);
});

test('ChurchSeeder leaves a church unassigned when its region slug is missing', function () {
    Region::where('slug', 'northern')->delete();

    $this->seed(ChurchSeeder::class);

    expect(Church::where('name', 'Auckland UPCI')->value('region_id'))->toBeNull();
});

test('ChurchSeeder is idempotent', function () {
    $this->seed(ChurchSeeder::class);
    $count = Church::count();

    $this->seed(ChurchSeeder::class);

    expect(Church::count())->toBe($count);
});

test('the DatabaseSeeder admin can access the panel', function () {
    $this->seed(DatabaseSeeder::class);

    $admin = User::where('email', 'admin@upci.co.nz')->firstOrFail();

    expect($admin->access_level)->toBe(AccessLevel::NATIONAL)
        ->and($admin->role)->toBe(UserRole::ADMINISTRATOR)
        ->and($admin->canAccessPanel(Filament::getPanel('admin')))->toBeTrue();
});

test('the national factory state passes the panel gate and the default state does not', function () {
    $panel = Filament::getPanel('admin');

    expect(User::factory()->national()->create()->canAccessPanel($panel))->toBeTrue()
        ->and(User::factory()->create()->canAccessPanel($panel))->toBeFalse();
});
