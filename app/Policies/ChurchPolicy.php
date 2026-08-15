<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Church;

class ChurchPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->access_level;
    }

    public function view(User $user, Church $church): bool
    {
        return $user->canAccessChurch($church);
    }

    public function create(User $user): bool
    {
        return $user->isNational();
    }

    public function update(User $user, Church $church): bool
    {
        if ($user->isNational()) {
            return true;
        }

        if ($user->isRegional()) {
            return $church->region_id === $user->region_id;
        }

        if ($user->isLocal()) {
            return $church->id === $user->church_id;
        }

        return false;
    }

    public function delete(User $user, Church $church): bool
    {
        return $user->isNational();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isNational();
    }
}
