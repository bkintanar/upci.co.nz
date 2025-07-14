<div class="flex items-start max-md:flex-col">
    <div class="me-10 w-full pb-4 md:w-[220px]">
        <nav class="space-y-1">
            <a href="{{ route('settings.profile') }}" wire:navigate class="flex items-center px-3 py-2 text-sm font-medium rounded-md {{ request()->routeIs('settings.profile') ? 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white' : 'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800' }}">
                {{ __('Profile') }}
            </a>
            <a href="{{ route('settings.password') }}" wire:navigate class="flex items-center px-3 py-2 text-sm font-medium rounded-md {{ request()->routeIs('settings.password') ? 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white' : 'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800' }}">
                {{ __('Password') }}
            </a>
            <a href="{{ route('settings.appearance') }}" wire:navigate class="flex items-center px-3 py-2 text-sm font-medium rounded-md {{ request()->routeIs('settings.appearance') ? 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white' : 'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800' }}">
                {{ __('Appearance') }}
            </a>
        </nav>
    </div>

    <div class="border-t border-gray-200 dark:border-gray-700 md:hidden"></div>

    <div class="flex-1 self-stretch max-md:pt-6">
        <div class="space-y-2">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $heading ?? '' }}</h2>
            <p class="text-base text-gray-600 dark:text-gray-400">{{ $subheading ?? '' }}</p>
        </div>

        <div class="mt-5 w-full max-w-lg">
            {{ $slot }}
        </div>
    </div>
</div>
