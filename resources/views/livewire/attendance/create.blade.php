<?php

use App\Models\Attendance;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;

new #[Layout('components.layouts.app')] class extends Component {
    #[Validate('required|date')]
    public $date;

    #[Validate('nullable|string|max:255')]
    public $event = '';

    #[Validate('required|integer|min:0')]
    public $mens = 0;

    #[Validate('required|integer|min:0')]
    public $ladies = 0;

    #[Validate('required|integer|min:0')]
    public $children = 0;

    #[Validate('required|integer|min:0')]
    public $visitors = 0;

    #[Validate('required|integer|min:0')]
    public $total_attendance = 0;

    public $entry_mode = 'detailed'; // 'detailed' or 'total'
    public $calculated_total = 0;

    public function mount()
    {
        $this->date = now()->format('Y-m-d');
        $this->updateCalculatedTotal();
    }

    public function updatedTotalAttendance()
    {
        $this->updateCalculatedTotal();
    }

    public function updatedDate()
    {
        $this->updateCalculatedTotal();
    }

    public function updated()
    {
        $this->updateCalculatedTotal();
    }

    public function updatedEntryMode()
    {
        if ($this->entry_mode === 'total') {
            $this->mens = 0;
            $this->ladies = 0;
            $this->children = 0;
            $this->visitors = 0;
        } else {
            $this->total_attendance = 0;
        }
        $this->updateCalculatedTotal();
    }

    public function updatedMens()
    {
        $this->updateCalculatedTotal();
    }

    public function updatedLadies()
    {
        $this->updateCalculatedTotal();
    }

    public function updatedChildren()
    {
        $this->updateCalculatedTotal();
    }

    public function updatedVisitors()
    {
        $this->updateCalculatedTotal();
    }

    public function updateCalculatedTotal()
    {
        $this->calculated_total = $this->entry_mode === 'detailed' ? $this->mens + $this->ladies + $this->children + $this->visitors : $this->total_attendance;
    }

    public function save()
    {
        $this->validate();

        // If using total mode, store the total in the visitors field
        if ($this->entry_mode === 'total') {
            $this->visitors = $this->total_attendance;
            $this->mens = 0;
            $this->ladies = 0;
            $this->children = 0;
        }

        Attendance::create([
            'date' => $this->date,
            'event' => $this->event,
            'mens' => $this->mens,
            'ladies' => $this->ladies,
            'children' => $this->children,
            'visitors' => $this->visitors,
            'user_id' => Auth::id(),
        ]);

        session()->flash('success', 'Attendance record saved successfully.');
        $this->redirect(route('attendance.index'));
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Add Attendance Record</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Record new church attendance data
                </p>
            </div>
            <x-button href="{{ route('attendance.index') }}" secondary wire:navigate>
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to List
            </x-button>
        </div>
    </div>

    <!-- Form -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <form wire:submit="save" class="space-y-6">
            <!-- Basic Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <x-input
                        wire:model.live="date"
                        label="Date"
                        type="date"
                        required
                    />
                </div>
                <div>
                    <x-input
                        wire:model.live="event"
                        label="Event (Optional)"
                        placeholder="e.g., Sunday Service, Prayer Meeting, etc."
                    />
                </div>
            </div>

            <!-- Entry Mode Selection -->
            <div class="space-y-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Entry Mode</h3>
                <div class="flex space-x-4">
                    <button
                        type="button"
                        wire:click="$set('entry_mode', 'detailed')"
                        class="flex items-center px-4 py-2 rounded-lg border {{ $entry_mode === 'detailed' ? 'bg-blue-50 border-blue-200 text-blue-700 dark:bg-blue-900/20 dark:border-blue-800 dark:text-blue-300' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700' }}"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        Detailed Count
                    </button>
                    <button
                        type="button"
                        wire:click="$set('entry_mode', 'total')"
                        class="flex items-center px-4 py-2 rounded-lg border {{ $entry_mode === 'total' ? 'bg-blue-50 border-blue-200 text-blue-700 dark:bg-blue-900/20 dark:border-blue-800 dark:text-blue-300' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700' }}"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path>
                        </svg>
                        Total Only
                    </button>
                </div>
            </div>

            <!-- Attendance Input -->
            @if($entry_mode === 'detailed')
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Detailed Attendance</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <x-input
                                wire:model.live="mens"
                                label="Men"
                                type="number"
                                min="0"
                                required
                            />
                        </div>
                        <div>
                            <x-input
                                wire:model.live="ladies"
                                label="Ladies"
                                type="number"
                                min="0"
                                required
                            />
                        </div>
                        <div>
                            <x-input
                                wire:model.live="children"
                                label="Children"
                                type="number"
                                min="0"
                                required
                            />
                        </div>
                        <div>
                            <x-input
                                wire:model.live="visitors"
                                label="Visitors"
                                type="number"
                                min="0"
                                required
                            />
                        </div>
                    </div>
                </div>
            @else
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Total Attendance</h3>
                    <div class="max-w-xs">
                        <x-input
                            wire:model.live="total_attendance"
                            label="Total Attendees"
                            type="number"
                            min="0"
                            required
                        />
                    </div>
                </div>
            @endif

            <!-- Live Preview -->
            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Preview</h4>
                <div class="flex items-center space-x-6">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center">
                            <span class="text-white font-bold text-lg">{{ $calculated_total }}</span>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">Total Attendance</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $date ? \Carbon\Carbon::parse($date)->format('M j, Y') : 'Select date' }}
                            </p>
                        </div>
                    </div>

                    @if($entry_mode === 'detailed')
                        <div class="flex space-x-2">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                M: {{ $mens }}
                            </span>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-200">
                                L: {{ $ladies }}
                            </span>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                C: {{ $children }}
                            </span>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                V: {{ $visitors }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end space-x-4">
                <x-button href="{{ route('attendance.index') }}" secondary wire:navigate>
                    Cancel
                </x-button>
                <x-button primary type="submit">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Save Record
                </x-button>
            </div>
        </form>
    </div>
</div>
