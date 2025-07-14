@props([
    'heading' => null,
    'expandable' => false,
])

@php
    $content = $slot->toHtml();
    $isExpandable = $expandable && $heading;
@endphp

@if ($isExpandable)
    <li>
        <details>
            <summary class="text-sm font-medium text-zinc-500 dark:text-zinc-400 px-3 py-2 cursor-pointer list-none">
                {{ $heading }}
                <svg class="hidden w-3 h-3 inline ml-auto group-open:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
                <svg class="block w-3 h-3 inline ml-auto group-open:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </summary>
            <div class="ml-4">
                {!! $content !!}
            </div>
        </details>
    </li>
@else
    @if ($heading)
        <li class="text-sm font-medium text-zinc-500 dark:text-zinc-400 px-3 py-2">
            {{ $heading }}
        </li>
    @endif
    {!! $content !!}
@endif
