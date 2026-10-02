<?php

use Livewire\Volt\Volt;

beforeEach(fn () => $this->markTestSkipped('Stale starter-kit test: Volt auth.register no longer exists and the app has no self-registration (/register redirects to /admin/login). See tests/Feature/FilamentAuthTest.php.'));

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = Volt::test('auth.register')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register');

    $response
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});
