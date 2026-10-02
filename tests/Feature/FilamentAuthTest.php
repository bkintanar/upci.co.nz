<?php

use App\Models\User;
use Livewire\Livewire;
use App\Enums\UserRole;
use App\Enums\AccessLevel;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Filament\Auth\Notifications\ResetPassword;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset;

// Filament owns authentication. The legacy starter-kit URLs in routes/auth.php
// redirect into the panel, and they must be registered before the SPA
// catch-all, otherwise `/login` silently serves the Vue app with a 200.

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->admin = User::factory()->create([
        'role' => UserRole::ADMINISTRATOR,
        'access_level' => AccessLevel::NATIONAL,
    ]);
});

test('the panel login page renders', function () {
    $this->get('/admin/login')->assertOk();
});

test('a panel user can log in with the right password', function () {
    Livewire::test(Login::class)
        ->fillForm(['email' => $this->admin->email, 'password' => 'password'])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    $this->assertAuthenticatedAs($this->admin);
});

test('a wrong password is rejected', function () {
    Livewire::test(Login::class)
        ->fillForm(['email' => $this->admin->email, 'password' => 'wrong-password'])
        ->call('authenticate')
        ->assertHasFormErrors(['email']);

    $this->assertGuest();
});

test('a user with a NULL access_level cannot log in to the panel', function () {
    $member = User::factory()->create([
        'role' => UserRole::MEMBER,
        'access_level' => null,
    ]);

    Livewire::test(Login::class)
        ->fillForm(['email' => $member->email, 'password' => 'password'])
        ->call('authenticate')
        ->assertHasFormErrors(['email']);

    $this->assertGuest();
});

test('the panel password reset request page renders', function () {
    $this->get('/admin/password-reset/request')->assertOk();
});

test('requesting a password reset sends the Filament reset notification', function () {
    Notification::fake();

    Livewire::test(RequestPasswordReset::class)
        ->fillForm(['email' => $this->admin->email])
        ->call('request')
        ->assertHasNoFormErrors();

    Notification::assertSentTo($this->admin, ResetPassword::class);
});

test('requesting a password reset for an unknown email sends nothing', function () {
    Notification::fake();

    Livewire::test(RequestPasswordReset::class)
        ->fillForm(['email' => 'nobody@example.com'])
        ->call('request');

    Notification::assertNothingSent();
});

test('/login redirects to the panel login instead of serving the SPA', function () {
    $this->get('/login')->assertRedirect('/admin/login');
});

test('/register redirects to the panel login because the panel has no registration', function () {
    $this->get('/register')->assertRedirect('/admin/login');
});

test('/forgot-password redirects to the panel password reset request', function () {
    $this->get('/forgot-password')->assertRedirect('/admin/password-reset/request');
});

test('/reset-password/{token} redirects to the panel password reset request', function () {
    $this->get('/reset-password/some-token')->assertRedirect('/admin/password-reset/request');
});

test('logging out ends the session', function () {
    $this->actingAs($this->admin)->post('/logout')->assertRedirect('/');

    $this->assertGuest();
});

test('SPA paths still fall through to the Vue app', function () {
    $this->get('/about-us')->assertOk()->assertViewIs('app');
    $this->get('/apiary')->assertOk()->assertViewIs('app');
});

test('unknown API paths are a JSON 404, not the SPA', function () {
    $this->get('/api/does-not-exist')
        ->assertNotFound()
        ->assertHeader('Content-Type', 'application/json')
        ->assertJsonStructure(['message']);
});
