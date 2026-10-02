<?php

namespace App\Filament\Resources\Churches\Concerns;

use App\Models\User;
use App\Models\Church;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Filament\Resources\Churches\Schemas\ChurchForm;

/**
 * Persists the "Assign Leadership" select on save.
 *
 * The select is dehydrated(false), so nothing reaches the model. Every user being
 * added to or removed from the church is authorised with UserPolicy::update in the
 * before* hook, so a forbidden change is refused (403) before the church or any user
 * is written. The after* hook then applies the change.
 */
trait SyncsLeadership
{
    /**
     * @var array{add: array<int, int>, remove: array<int, int>}|null
     */
    protected ?array $pendingLeadershipSync = null;

    /**
     * Work out and authorise the leadership change for $church (null while creating).
     */
    protected function authorizeLeadershipSync(?Church $church): void
    {
        $this->pendingLeadershipSync = null;

        // Local users see the field disabled; never act on (possibly tampered) state for them.
        if (auth()->user()?->isLocal()) {
            return;
        }

        $selected = $this->form->getRawState()['assigned_leadership'] ?? [];
        $selected = array_values(array_unique(array_map('intval', is_array($selected) ? $selected : [])));

        $current = $church
            ? User::query()
                ->where('church_id', $church->id)
                ->whereIn('role', ChurchForm::leadershipRoles())
                ->pluck('id')
                ->all()
            : [];

        // Only leadership-role users can be pulled in through this field.
        $toAdd = User::query()
            ->whereIn('id', array_diff($selected, $current))
            ->whereIn('role', ChurchForm::leadershipRoles())
            ->pluck('id')
            ->all();

        $toRemove = array_values(array_diff($current, $selected));

        foreach (User::with('church')->whereIn('id', [...$toAdd, ...$toRemove])->get() as $user) {
            Gate::authorize('update', $user); // 403 before any write
        }

        $this->pendingLeadershipSync = ['add' => $toAdd, 'remove' => $toRemove];
    }

    /**
     * Apply the change authorised by authorizeLeadershipSync().
     */
    protected function applyLeadershipSync(Church $church): void
    {
        $pending = $this->pendingLeadershipSync;
        $this->pendingLeadershipSync = null;

        if (! $pending || ($pending['add'] === [] && $pending['remove'] === [])) {
            return;
        }

        DB::transaction(function () use ($church, $pending): void {
            if ($pending['add'] !== []) {
                User::whereIn('id', $pending['add'])->update(['church_id' => $church->id]);
            }

            if ($pending['remove'] !== []) {
                User::whereIn('id', $pending['remove'])->update(['church_id' => null]);
            }
        });
    }
}
