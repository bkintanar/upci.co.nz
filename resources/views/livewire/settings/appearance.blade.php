<?php

use Livewire\Volt\Component;

new class extends Component {
    //
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Appearance')" :subheading=" __('Update the appearance settings for your account')">
        <div x-data="{ appearance: $persist('system') }" class="space-y-4">
            <div class="inline-flex rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 p-1">
                <button
                    @click="appearance = 'light'; $el.closest('[x-data]').dispatchEvent(new CustomEvent('appearance-changed', { detail: { value: 'light' } }))"
                    :class="appearance === 'light' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                    class="flex items-center space-x-2 px-3 py-2 text-sm font-medium rounded-md transition-colors"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <span>{{ __('Light') }}</span>
                </button>
                <button
                    @click="appearance = 'dark'; $el.closest('[x-data]').dispatchEvent(new CustomEvent('appearance-changed', { detail: { value: 'dark' } }))"
                    :class="appearance === 'dark' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                    class="flex items-center space-x-2 px-3 py-2 text-sm font-medium rounded-md transition-colors"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                    </svg>
                    <span>{{ __('Dark') }}</span>
                </button>
                <button
                    @click="appearance = 'system'; $el.closest('[x-data]').dispatchEvent(new CustomEvent('appearance-changed', { detail: { value: 'system' } }))"
                    :class="appearance === 'system' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                    class="flex items-center space-x-2 px-3 py-2 text-sm font-medium rounded-md transition-colors"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    <span>{{ __('System') }}</span>
                </button>
            </div>
        </div>
    </x-settings.layout>
</section>
