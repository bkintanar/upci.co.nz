<?php

use App\Models\User;
use App\Models\Church;
use App\Models\Region;
use Livewire\Livewire;
use App\Enums\UserRole;
use App\Enums\AccessLevel;
use App\Models\Attendance;
use Filament\Facades\Filament;
use App\Filament\Resources\Attendances\Pages\EditAttendance;

/*
 * The church picker added to AttendanceForm for regional and national users is
 * also on the edit page, so editing is a second way to set a row's church. It
 * must hold to the same scope as create.
 */
beforeEach(function () {
    Filament::setCurrentPanel('admin');

    $this->region = Region::create(['name' => 'Edit Scope North', 'slug' => 'edit-scope-north', 'sort_order' => 1]);
    $this->otherRegion = Region::create(['name' => 'Edit Scope South', 'slug' => 'edit-scope-south', 'sort_order' => 2]);
    $this->church = Church::create(['name' => 'In Region', 'region_id' => $this->region->id, 'is_active' => true]);
    $this->sibling = Church::create(['name' => 'Also In Region', 'region_id' => $this->region->id, 'is_active' => true]);
    $this->outside = Church::create(['name' => 'Out Of Region', 'region_id' => $this->otherRegion->id, 'is_active' => true]);
});

function attendanceEditScopeUser(string $level, array $extra = []): User
{
    return User::create(array_merge([
        'name' => 'T',
        'email' => $level.'-edit-scope@x',
        'password' => 'x',
        'role' => UserRole::ADMINISTRATOR,
        'access_level' => AccessLevel::from($level),
    ], $extra));
}

function attendanceEditScopeRow(Church $church, User $user): Attendance
{
    return Attendance::create([
        'church_id' => $church->id, 'user_id' => $user->id, 'date' => now()->toDateString(),
        'mens' => 1, 'ladies' => 0, 'youth' => 0, 'children' => 0, 'visitors' => 0,
    ]);
}

test('a regional user cannot move an attendance row to a church outside their region', function () {
    $regional = attendanceEditScopeUser('regional', ['region_id' => $this->region->id]);
    $row = attendanceEditScopeRow($this->church, $regional);

    $this->actingAs($regional);

    Livewire::test(EditAttendance::class, ['record' => $row->getRouteKey()])
        ->set('data.church_id', $this->outside->id)
        ->call('save')
        ->assertHasFormErrors(['church_id']);

    expect($row->fresh()->church_id)->toBe($this->church->id);
});

test('a regional user can move an attendance row between churches in their region', function () {
    $regional = attendanceEditScopeUser('regional', ['region_id' => $this->region->id]);
    $row = attendanceEditScopeRow($this->church, $regional);

    $this->actingAs($regional);

    Livewire::test(EditAttendance::class, ['record' => $row->getRouteKey()])
        ->set('data.church_id', $this->sibling->id)
        ->call('save')
        ->assertHasNoFormErrors();

    expect($row->fresh()->church_id)->toBe($this->sibling->id);
});

test('a local user cannot move their attendance row by tampering with the church', function () {
    $local = attendanceEditScopeUser('local', ['church_id' => $this->church->id]);
    $row = attendanceEditScopeRow($this->church, $local);

    $this->actingAs($local);

    Livewire::test(EditAttendance::class, ['record' => $row->getRouteKey()])
        ->set('data.church_id', $this->outside->id) // not rendered for local users
        ->call('save')
        ->assertHasNoFormErrors();

    expect($row->fresh()->church_id)->toBe($this->church->id);
});
