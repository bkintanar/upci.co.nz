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
                <div class="md:col-span-2" wire:key="date-field">
                    <flux:input
                        wire:model="date"
                        label="Date"
                        type="date"
                        required
                    />
                </div>

                <!-- Event -->
                <div class="md:col-span-2" wire:key="event-field">
                    <flux:input
                        wire:model="event"
                        label="Event"
                        placeholder="Optional event name (e.g., Sunday Service, Prayer Meeting)"
                    />
                </div>

                <!-- Entry Mode Toggle -->
                <div class="md:col-span-2" wire:key="entry-mode-field">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-3">
                        Entry Mode
                    </label>
                    <flux:radio.group wire:model.live="entry_mode" variant="segmented">
                        <flux:radio value="detailed">Detailed Count</flux:radio>
                        <flux:radio value="total">Total Only</flux:radio>
                    </flux:radio.group>
                </div>

                <!-- Always render all fields but hide/show with CSS -->
                <!-- Mens -->
                <div wire:key="mens-field" class="{{ $entry_mode === 'detailed' ? '' : 'hidden' }}">
                    <flux:input
                        wire:model.change="mens"
                        label="Mens"
                        type="number"
                        min="0"
                        :required="$entry_mode === 'detailed'"
                    />
                </div>


                <!-- Ladies -->
                <div wire:key="ladies-field" class="{{ $entry_mode === 'detailed' ? '' : 'hidden' }}">
                    <flux:input
                        wire:model.change="ladies"
                        label="Ladies"
                        type="number"
                        min="0"
                        :required="$entry_mode === 'detailed'"
                    />
                </div>

                <!-- Children -->
                <div wire:key="children-field" class="{{ $entry_mode === 'detailed' ? '' : 'hidden' }}">
                    <flux:input
                        wire:model.change="children"
                        label="Children"
                        type="number"
                        min="0"
                        :required="$entry_mode === 'detailed'"
                    />
                </div>

                <!-- Visitors -->
                <div wire:key="visitors-field" class="{{ $entry_mode === 'detailed' ? '' : 'hidden' }}">
                    <flux:input
                        wire:model.change="visitors"
                        label="Visitors"
                        type="number"
                        :required="$entry_mode === 'detailed'"
                    />
                </div>

                <!-- Total Attendance -->
                <div wire:key="total-field" class="md:col-span-2 {{ $entry_mode === 'total' ? '' : 'hidden' }}">
                    <flux:input
                        wire:model.change="total_attendance"
                        label="Total Attendance"
                        type="number"
                        min="0"
                        :required="$entry_mode === 'total'"
                        placeholder="Enter total number of attendees"
                    />
                </div>
            </div>

            <!-- Total Preview -->
            <div class="mt-6 bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                <div class="flex justify-between items-center gap-4">
                    <span class="font-medium text-gray-700 dark:text-gray-300 flex-shrink-0">
                        @if($entry_mode === 'detailed')
                            Calculated Total:
                        @else
                            Total Attendance:
                        @endif
                    </span>
                    <div class="flex-shrink-0">
                        <flux:input
                            wire:model="calculated_total"
                            class="text-4xl font-bold text-blue-600 dark:text-blue-400 text-center border-0 bg-transparent focus:ring-0 focus:border-0 p-0 w-auto min-w-20"
                            disabled
                            readonly
                        />
                    </div>
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
