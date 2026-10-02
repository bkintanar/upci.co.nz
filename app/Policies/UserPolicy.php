<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->access_level;
    }

    public function view(User $actor, User $target): bool
    {
        if ($actor->id === $target->id || $actor->isNational()) {
            return true;
        }

        return $this->outranks($actor, $target) && $actor->canAccessChurch($target->church);
    }

    public function create(User $user): bool
    {
        return $user->isNational();
    }

    public function update(User $actor, User $target): bool
    {
        if ($actor->id === $target->id || $actor->isNational()) {
            return true;
        }

        // A regional user may only act on someone of strictly lower rank.
        // Matching on the target's church alone let a presbyter set the
        // password of a national administrator who happened to attend a
        // church in their region.
        if ($actor->isRegional()) {
            return $actor->region_id !== null
                && $this->outranks($actor, $target)
                && $target->church?->region_id === $actor->region_id;
        }

        return false;
    }

    public function delete(User $actor, User $target): bool
    {
        return $actor->isNational() && $actor->id !== $target->id;
    }

    public function deleteAny(User $user): bool
    {
        return $user->isNational();
    }

    /**
     * Whether a non-national actor ranks above the target. Regional outranks
     * local and no-access users; nobody but national outranks a regional or
     * national user. National actors are handled before this is reached.
     */
    private function outranks(User $actor, User $target): bool
    {
        return ! $target->isNational() && ! $target->isRegional();
    }
}
