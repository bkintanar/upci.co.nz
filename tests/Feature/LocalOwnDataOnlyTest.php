<?php

use App\Models\User;
use App\Models\Church;
use App\Models\Region;
use App\Enums\UserRole;
use App\Enums\AccessLevel;
use Filament\Facades\Filament;
use App\Filament\Resources\Users\UserResource;

/*
 * Local (church-level) admins see their church's data and nothing more, the
 * same rule as regional ones (owner, 2026-10-15: "tighten local admins the
 * same way"). Their user list included anyone attending the church, national
 * board members among them, and an empty Regions page.
 */
beforeEach(function () {
    Filament::setCurrentPanel('admin');

    $this->north = Region::firstOrCreate(['slug' => 'northern'], ['name' => 'Northern Region', 'sort_order' => 1]);
    $this->church = Church::create(['name' => 'Home Church', 'region_id' => $this->north->id, 'is_active' => true]);
    $this->otherChurch = Church::create(['name' => 'Other Church', 'region_id' => $this->north->id, 'is_active' => true]);

    $this->local = User::create([
        'name' => 'Local', 'email' => 'local-only@x', 'password' => 'x',
        'role' => UserRole::SENIOR_PASTOR, 'access_level' => AccessLevel::LOCAL, 'church_id' => $this->church->id,
    ]);
});

function localOnlyUser(string $email, ?AccessLevel $level, int $churchId, UserRole $role = UserRole::USHER): User
{
    return User::create(['name' => $email, 'email' => $email, 'password' => 'x', 'role' => $role, 'access_level' => $level, 'church_id' => $churchId]);
}

test('a local admin lists themselves and their church\'s members only', function () {
    $member = localOnlyUser('member@x', null, $this->church->id);
    $boardMember = localOnlyUser('board@x', AccessLevel::NATIONAL, $this->church->id, UserRole::EXECUTIVE_BOARD);
    $regional = localOnlyUser('regional@x', AccessLevel::REGIONAL, $this->church->id, UserRole::REGIONAL_PRESBYTER);
    $otherAdmin = localOnlyUser('other-admin@x', AccessLevel::LOCAL, $this->church->id, UserRole::PASTOR);
    $elsewhere = localOnlyUser('elsewhere@x', null, $this->otherChurch->id);

    $this->actingAs($this->local);
    $ids = UserResource::getEloquentQuery()->pluck('id')->all();

    expect($ids)->toContain($this->local->id, $member->id)
        ->not->toContain($boardMember->id)
        ->not->toContain($regional->id)
        ->not->toContain($otherAdmin->id)
        ->not->toContain($elsewhere->id);
});

test('a board member at the local admin\'s church cannot be opened by them', function () {
    $boardMember = localOnlyUser('board-open@x', AccessLevel::NATIONAL, $this->church->id, UserRole::EXECUTIVE_BOARD);

    $this->actingAs($this->local)->get("/admin/users/{$boardMember->id}/edit")->assertNotFound();
});

test('regions are hidden from local admins', function () {
    expect($this->local->can('viewAny', Region::class))->toBeFalse();

    $this->actingAs($this->local)->get('/admin/regions')->assertForbidden();
});

test('regional and national admins still see regions', function () {
    $regional = localOnlyUser('regional-regions@x', AccessLevel::REGIONAL, $this->church->id, UserRole::REGIONAL_PRESBYTER);
    $national = localOnlyUser('national-regions@x', AccessLevel::NATIONAL, $this->church->id, UserRole::EXECUTIVE_BOARD);

    expect($regional->can('viewAny', Region::class))->toBeTrue()
        ->and($national->can('viewAny', Region::class))->toBeTrue();
});
