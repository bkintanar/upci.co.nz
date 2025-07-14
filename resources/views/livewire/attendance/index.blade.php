<?php

use App\Models\Attendance;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    public $search = '';
    public $sortBy = 'date';
    public $sortDirection = 'desc';

    public function delete($id)
    {
        $attendance = Attendance::findOrFail($id);
        $attendance->delete();

        session()->flash('success', 'Attendance record deleted successfully.');
    }

    public function sortBy($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function with(): array
    {
        return [
            'attendances' => Attendance::with('user')
                ->when($this->search, fn ($q) => $q->where('event', 'like', '%' . $this->search . '%'))
                ->orderBy($this->sortBy, $this->sortDirection)
                ->paginate(10)
        ];
    }
}; ?>

<div class="space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <flux:heading size="xl">Church Attendance</flux:heading>
                <flux:subheading>Manage and track church attendance records</flux:subheading>
            </div>
            <flux:button :href="route('attendance.create')" variant="primary" wire:navigate>
                Add New Record
            </flux:button>
        </div>

        @if(session('success'))
            <div class="mb-4 rounded-md border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/20">
                <flux:text class="font-medium text-green-600 dark:text-green-400">
                    {{ session('success') }}
                </flux:text>
            </div>
        @endif

        <div class="flex gap-4 items-center">
                <flux:input
                    wire:model.live="search"
                    placeholder="Search by event..."
                    class="flex-1"
                />
                <flux:button variant="ghost" wire:click="$refresh">
                    Refresh
                </flux:button>
            </div>

        <div class="overflow-x-auto">
                <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <table class="w-full table-auto">
                        <thead>
                            <tr class="bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
                                <th class="px-4 py-3 text-left text-sm font-medium text-zinc-900 dark:text-zinc-100 cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-700"
                                    wire:click="sortBy('date')">
                                    Date
                                    @if($sortBy === 'date')
                                        @if($sortDirection === 'asc') ↑ @else ↓ @endif
                                    @endif
                                </th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-zinc-900 dark:text-zinc-100">Event</th>
                                <th class="px-4 py-3 text-center text-sm font-medium text-zinc-900 dark:text-zinc-100">Mens</th>
                                <th class="px-4 py-3 text-center text-sm font-medium text-zinc-900 dark:text-zinc-100">Ladies</th>
                                <th class="px-4 py-3 text-center text-sm font-medium text-zinc-900 dark:text-zinc-100">Children</th>
                                <th class="px-4 py-3 text-center text-sm font-medium text-zinc-900 dark:text-zinc-100">Visitors</th>
                                <th class="px-4 py-3 text-center text-sm font-medium text-zinc-900 dark:text-zinc-100">Total</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-zinc-900 dark:text-zinc-100">Added By</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-zinc-900 dark:text-zinc-100">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse($attendances as $attendance)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                    <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">
                                        {{ $attendance->date->format('M d, Y') }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">
                                        {{ $attendance->event ?? 'Regular Service' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-center text-zinc-900 dark:text-zinc-100">
                                        {{ $attendance->mens }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-center text-zinc-900 dark:text-zinc-100">
                                        {{ $attendance->ladies }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-center text-zinc-900 dark:text-zinc-100">
                                        {{ $attendance->children }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-center text-zinc-900 dark:text-zinc-100">
                                        {{ $attendance->visitors }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-center font-bold text-zinc-900 dark:text-zinc-100">
                                        {{ $attendance->total }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">
                                        {{ $attendance->user->name }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <flux:button size="sm" variant="ghost" wire:click="delete({{ $attendance->id }})"
                                                   wire:confirm="Are you sure you want to delete this record?">
                                            Delete
                                        </flux:button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                        No attendance records found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        <div class="flex justify-center">
            {{ $attendances->links() }}
        </div>
    </div>
