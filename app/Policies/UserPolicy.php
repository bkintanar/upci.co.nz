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
        if ($actor->id === $target->id) {
            return true;
        }

        return $actor->canAccessChurch($target->church);
    }

    public function create(User $user): bool
    {
        return $user->isNational();
    }

    public function update(User $actor, User $target): bool
    {
        if ($actor->id === $target->id) {
            return true;
        }

        if ($actor->isNational()) {
            return true;
        }

        if ($actor->isRegional()) {
            return $target->church?->region_id === $actor->region_id;
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
}
