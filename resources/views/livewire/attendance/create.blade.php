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

    public function mount()
    {
        $this->date = now()->format('Y-m-d');
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
    }

    public function save()
    {
        if ($this->entry_mode === 'detailed') {
            $this->validate([
                'date' => 'required|date',
                'event' => 'nullable|string|max:255',
                'mens' => 'required|integer|min:0',
                'ladies' => 'required|integer|min:0',
                'children' => 'required|integer|min:0',
                'visitors' => 'required|integer|min:0',
            ]);
        } else {
            $this->validate([
                'date' => 'required|date',
                'event' => 'nullable|string|max:255',
                'total_attendance' => 'required|integer|min:0',
            ]);
        }

        $data = [
            'date' => $this->date,
            'event' => $this->event ?: null,
            'user_id' => Auth::id(),
        ];

        if ($this->entry_mode === 'detailed') {
            $data['mens'] = $this->mens;
            $data['ladies'] = $this->ladies;
            $data['children'] = $this->children;
            $data['visitors'] = $this->visitors;
        } else {
            $data['mens'] = 0;
            $data['ladies'] = 0;
            $data['children'] = 0;
            $data['visitors'] = $this->total_attendance;
        }

        Attendance::create($data);

        session()->flash('success', 'Attendance record created successfully.');

        return redirect()->route('attendance.index');
    }

    public function getTotal()
    {
        return $this->entry_mode === 'detailed'
            ? $this->mens + $this->ladies + $this->children + $this->visitors
            : $this->total_attendance;
    }
}; ?>

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <flux:heading size="xl">New Church Attendance</flux:heading>
            <flux:subheading>Add a new attendance record for the church service</flux:subheading>
        </div>
        <flux:button :href="route('attendance.index')" variant="ghost" wire:navigate>
            ← Back to List
        </flux:button>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Date -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        Date *
                    </label>
                    <input
                        type="date"
                        wire:model="date"
                        class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                        required
                    >
                </div>

                <!-- Event -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        Event
                    </label>
                    <input
                        type="text"
                        wire:model="event"
                        placeholder="Optional event name (e.g., Sunday Service, Prayer Meeting)"
                        class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                    >
                </div>

                <!-- Entry Mode Toggle -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-3">
                        Entry Mode
                    </label>
                    <div class="flex gap-4">
                        <label class="flex items-center">
                            <input
                                type="radio"
                                wire:model.live="entry_mode"
                                value="detailed"
                                class="mr-2"
                            >
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">Detailed Count</span>
                        </label>
                        <label class="flex items-center">
                            <input
                                type="radio"
                                wire:model.live="entry_mode"
                                value="total"
                                class="mr-2"
                            >
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">Total Only</span>
                        </label>
                    </div>
                </div>

                @if($entry_mode === 'detailed')
                    <!-- Mens -->
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            Mens *
                        </label>
                        <input
                            type="number"
                            wire:model.live="mens"
                            min="0"
                            class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                            required
                        >
                    </div>

                    <!-- Ladies -->
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            Ladies *
                        </label>
                        <input
                            type="number"
                            wire:model.live="ladies"
                            min="0"
                            class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                            required
                        >
                    </div>

                    <!-- Children -->
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            Children *
                        </label>
                        <input
                            type="number"
                            wire:model.live="children"
                            min="0"
                            class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                            required
                        >
                    </div>

                    <!-- Visitors -->
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            Visitors *
                        </label>
                        <input
                            type="number"
                            wire:model.live="visitors"
                            min="0"
                            class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                            required
                        >
                    </div>
                @else
                    <!-- Total Attendance -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                            Total Attendance *
                        </label>
                        <input
                            type="number"
                            wire:model.live="total_attendance"
                            min="0"
                            placeholder="Enter total number of attendees"
                            class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                            required
                        >
                    </div>
                @endif
            </div>

            <!-- Total Preview -->
            <div class="mt-6 bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <div class="flex justify-between items-center">
                    <span class="font-medium text-gray-700 dark:text-gray-300">
                        @if($entry_mode === 'detailed')
                            Calculated Total:
                        @else
                            Total Attendance:
                        @endif
                    </span>
                    <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                        {{ $this->getTotal() }}
                    </span>
                </div>
                @if($entry_mode === 'detailed')
                    <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        {{ $mens }} mens + {{ $ladies }} ladies + {{ $children }} children + {{ $visitors }} visitors
                    </div>
                @endif
            </div>

            <div class="flex gap-4 pt-6">
                <flux:button type="submit" variant="primary" class="flex-1">
                    Save Attendance Record
                </flux:button>
                <flux:button :href="route('attendance.index')" variant="ghost" wire:navigate>
                    Cancel
                </flux:button>
            </div>
        </div>
    </form>
</div>
