<?php

use App\Models\User;
use App\Models\Church;
use App\Models\Region;
use App\Enums\UserRole;
use App\Enums\AccessLevel;

beforeEach(function () {
    $this->regionNorth = Region::firstOrCreate(['slug' => 'northern'], ['name' => 'Northern Region', 'sort_order' => 1]);
    $this->regionSouth = Region::firstOrCreate(['slug' => 'southern'], ['name' => 'Southern Region', 'sort_order' => 3]);
    $this->myChurch = Church::create(['name' => 'Mine', 'region_id' => $this->regionNorth->id, 'is_active' => true]);
    $this->otherChurch = Church::create(['name' => 'Other', 'region_id' => $this->regionSouth->id, 'is_active' => true]);
});

test('local user can update their own church', function () {
    $user = User::create([
        'name' => 'L', 'email' => 'l@x', 'password' => 'x',
        'role' => UserRole::SENIOR_PASTOR,
        'access_level' => AccessLevel::LOCAL,
        'church_id' => $this->myChurch->id,
    ]);

    expect($user->can('update', $this->myChurch))->toBeTrue();
});

test('local user cannot update a different church', function () {
    $user = User::create([
        'name' => 'L', 'email' => 'l@x', 'password' => 'x',
        'role' => UserRole::SENIOR_PASTOR,
        'access_level' => AccessLevel::LOCAL,
        'church_id' => $this->myChurch->id,
    ]);

    expect($user->can('update', $this->otherChurch))->toBeFalse();
});

test('local user without church_id cannot update any church', function () {
    $user = User::create([
        'name' => 'L', 'email' => 'l@x', 'password' => 'x',
        'role' => UserRole::SENIOR_PASTOR,
        'access_level' => AccessLevel::LOCAL,
        'church_id' => null,
    ]);

    expect($user->can('update', $this->myChurch))->toBeFalse();
});

test('local user still cannot create a church', function () {
    $user = User::create([
        'name' => 'L', 'email' => 'l@x', 'password' => 'x',
        'role' => UserRole::SENIOR_PASTOR,
        'access_level' => AccessLevel::LOCAL,
        'church_id' => $this->myChurch->id,
    ]);

    expect($user->can('create', Church::class))->toBeFalse();
});

test('local user still cannot delete their church', function () {
    $user = User::create([
        'name' => 'L', 'email' => 'l@x', 'password' => 'x',
        'role' => UserRole::SENIOR_PASTOR,
        'access_level' => AccessLevel::LOCAL,
        'church_id' => $this->myChurch->id,
    ]);

    expect($user->can('delete', $this->myChurch))->toBeFalse();
});

test('regional user can update churches in their region', function () {
    $user = User::create([
        'name' => 'R', 'email' => 'r@x', 'password' => 'x',
        'role' => UserRole::REGIONAL_PRESBYTER,
        'access_level' => AccessLevel::REGIONAL,
        'region_id' => $this->regionNorth->id,
    ]);

    expect($user->can('update', $this->myChurch))->toBeTrue()
        ->and($user->can('update', $this->otherChurch))->toBeFalse();
});

test('national user can update any church', function () {
    $user = User::create([
        'name' => 'N', 'email' => 'n@x', 'password' => 'x',
        'role' => UserRole::ADMINISTRATOR,
        'access_level' => AccessLevel::NATIONAL,
    ]);

    expect($user->can('update', $this->myChurch))->toBeTrue()
        ->and($user->can('update', $this->otherChurch))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| "Assign Leadership" and region on the church form
|--------------------------------------------------------------------------
| The leadership select listed every leader in the country and rewrote
| church_id in afterStateUpdated, before save and with no authorisation, so a
| local pastor could pull anyone into their church. region_id was editable by
| local and regional users, which let them move a church out of scope.
*/

function churchFormUser(string $email, AccessLevel $level, array $extra = []): User
{
    return User::create(array_merge([
        'name' => $email, 'email' => $email, 'password' => 'x',
        'role' => UserRole::SENIOR_PASTOR,
        'access_level' => $level,
    ], $extra));
}

function churchFormLeader(string $name, ?int $churchId, array $extra = []): User
{
    return User::create(array_merge([
        'name' => $name, 'email' => str($name)->slug().'@x', 'password' => 'x',
        'role' => UserRole::PASTOR,
        'church_id' => $churchId,
    ], $extra));
}

function editChurchPage(Church $church): Livewire\Features\SupportTesting\Testable
{
    Filament\Facades\Filament::setCurrentPanel('admin');

    return Livewire\Livewire::test(App\Filament\Resources\Churches\Pages\EditChurch::class, ['record' => $church->id]);
}

test('a local user sees the leadership field disabled and no other church leaders', function () {
    $otherPastor = churchFormLeader('Southern Pastor Zed', $this->otherChurch->id);
    $this->actingAs(churchFormUser('local@x', AccessLevel::LOCAL, ['church_id' => $this->myChurch->id]));

    editChurchPage($this->myChurch)
        // Fields inside collapsible sections are keyed by their section.
        ->assertFormFieldIsDisabled('assigned_leadership')
        ->assertDontSee($otherPastor->name);
});

test('a local user cannot pull another church leader in by tampering with the state', function () {
    $otherPastor = churchFormLeader('Southern Pastor Zed', $this->otherChurch->id);
    $this->actingAs(churchFormUser('local@x', AccessLevel::LOCAL, ['church_id' => $this->myChurch->id]));

    editChurchPage($this->myChurch)
        ->set('data.assigned_leadership', [$otherPastor->id])
        ->call('save');

    expect($otherPastor->fresh()->church_id)->toBe($this->otherChurch->id);
});

test('a regional user selecting an out-of-region leader moves nobody, before or on save', function () {
    $otherPastor = churchFormLeader('Southern Pastor Zed', $this->otherChurch->id);
    $this->actingAs(churchFormUser('regional@x', AccessLevel::REGIONAL, [
        'role' => UserRole::REGIONAL_PRESBYTER, 'region_id' => $this->regionNorth->id,
    ]));

    $page = editChurchPage($this->myChurch)
        ->assertDontSee($otherPastor->name)
        ->set('data.assigned_leadership', [$otherPastor->id]);

    // Selecting used to write church_id immediately, with no save at all.
    expect($otherPastor->fresh()->church_id)->toBe($this->otherChurch->id);

    // Not one of the offered options, so the select's own validation refuses it.
    $page->call('save')->assertHasErrors(['data.assigned_leadership.0']);

    expect($otherPastor->fresh()->church_id)->toBe($this->otherChurch->id);
});

test('a regional user cannot detach a leader they are not allowed to edit', function () {
    // A national administrator attending a church in the region is listed (they are
    // a current leader), so validation passes; the policy check must still refuse.
    $national = churchFormLeader('National Admin', $this->myChurch->id, [
        'role' => UserRole::ADMINISTRATOR, 'access_level' => AccessLevel::NATIONAL,
    ]);
    $this->actingAs(churchFormUser('regional@x', AccessLevel::REGIONAL, [
        'role' => UserRole::REGIONAL_PRESBYTER, 'region_id' => $this->regionNorth->id,
    ]));

    editChurchPage($this->myChurch)
        ->set('data.name', 'Renamed')
        ->set('data.assigned_leadership', [])
        ->call('save')
        ->assertForbidden();

    expect($national->fresh()->church_id)->toBe($this->myChurch->id)
        ->and($this->myChurch->fresh()->name)->toBe('Mine');
});

test('a regional user can assign and detach leaders in their own region', function () {
    $northChurch = Church::create(['name' => 'North Two', 'region_id' => $this->regionNorth->id, 'is_active' => true]);
    $incoming = churchFormLeader('Incoming Pastor', $northChurch->id);
    $outgoing = churchFormLeader('Outgoing Pastor', $this->myChurch->id);
    $this->actingAs(churchFormUser('regional@x', AccessLevel::REGIONAL, [
        'role' => UserRole::REGIONAL_PRESBYTER, 'region_id' => $this->regionNorth->id,
    ]));

    editChurchPage($this->myChurch)
        ->set('data.assigned_leadership', [$incoming->id])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($incoming->fresh()->church_id)->toBe($this->myChurch->id)
        ->and($outgoing->fresh()->church_id)->toBeNull();
});

test('a national user creating a church keeps the selected leadership', function () {
    $leader = churchFormLeader('New Pastor', $this->otherChurch->id);
    $this->actingAs(churchFormUser('national@x', AccessLevel::NATIONAL, ['role' => UserRole::ADMINISTRATOR]));
    Filament\Facades\Filament::setCurrentPanel('admin');

    Livewire\Livewire::test(App\Filament\Resources\Churches\Pages\CreateChurch::class)
        ->set('data.name', 'Brand New Church')
        ->set('data.service_times', [])
        ->set('data.assigned_leadership', [$leader->id])
        ->call('create')
        ->assertHasNoFormErrors();

    $church = Church::where('name', 'Brand New Church')->firstOrFail();
    expect($leader->fresh()->church_id)->toBe($church->id);
});

test('local and regional users cannot move a church to another region', function (string $level) {
    $actor = $level === 'local'
        ? churchFormUser('local@x', AccessLevel::LOCAL, ['church_id' => $this->myChurch->id])
        : churchFormUser('regional@x', AccessLevel::REGIONAL, [
            'role' => UserRole::REGIONAL_PRESBYTER, 'region_id' => $this->regionNorth->id,
        ]);
    $this->actingAs($actor);

    editChurchPage($this->myChurch)
        ->assertFormFieldIsDisabled('region_id')
        ->set('data.region_id', $this->regionSouth->id)
        ->set('data.name', 'Renamed')
        ->call('save')
        ->assertHasNoFormErrors();

    // The rest of the form still saves; only the region is locked.
    expect($this->myChurch->fresh()->region_id)->toBe($this->regionNorth->id)
        ->and($this->myChurch->fresh()->name)->toBe('Renamed');
})->with(['local', 'regional']);

test('a national user can move a church to another region', function () {
    $this->actingAs(churchFormUser('national@x', AccessLevel::NATIONAL, ['role' => UserRole::ADMINISTRATOR]));

    editChurchPage($this->myChurch)
        ->set('data.region_id', $this->regionSouth->id)
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->myChurch->fresh()->region_id)->toBe($this->regionSouth->id);
});

test('church coordinates set on the form are saved', function () {
    // latitude/longitude were ->hidden(), which in v4 is neither rendered nor dehydrated.
    $this->actingAs(churchFormUser('national@x', AccessLevel::NATIONAL, ['role' => UserRole::ADMINISTRATOR]));

    editChurchPage($this->myChurch)
        ->set('data.latitude', -36.85)
        ->set('data.longitude', 174.76)
        ->call('save')
        ->assertHasNoFormErrors();

    $church = $this->myChurch->fresh();
    expect((float) $church->latitude)->toEqual(-36.85)
        ->and((float) $church->longitude)->toEqual(174.76);
});
