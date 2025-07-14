<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        event(new Registered(($user = User::create($validated))));

        Auth::login($user);

        $this->redirectIntended(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="register" class="flex flex-col gap-6">
        <!-- Name -->
        <x-input
            wire:model="form.name"
            label="{{ __('Name') }}"
            id="name"
            type="text"
            name="name"
            required
            autofocus
            autocomplete="name"
        />

        <!-- Email Address -->
        <x-input
            wire:model="form.email"
            label="{{ __('Email') }}"
            id="email"
            type="email"
            name="email"
            required
            autocomplete="username"
        />

        <!-- Password -->
        <x-input
            wire:model="form.password"
            label="{{ __('Password') }}"
            id="password"
            type="password"
            name="password"
            required
            autocomplete="new-password"
        />

        <!-- Confirm Password -->
        <x-input
            wire:model="form.password_confirmation"
            label="{{ __('Confirm Password') }}"
            id="password_confirmation"
            type="password"
            name="password_confirmation"
            required
            autocomplete="new-password"
        />

        <div class="flex items-center justify-end">
            <x-button type="submit" variant="primary" class="w-full">
                {{ __('Register') }}
            </x-button>
        </div>
    </form>

    <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
        {{ __('Already have an account?') }}
        <a href="{{ route('login') }}" wire:navigate class="text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300">
            {{ __('Log in') }}
        </a>
    </div>
</div>
