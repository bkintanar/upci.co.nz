<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Base policy for resources that only national-access users may manage
 * (CMS content, site-wide configuration, inbound contact messages, etc.).
 *
 * Extend this class to get the correct Filament nav + CRUD gating for free.
 */
abstract class NationalOnlyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isNational();
    }

    public function view(User $user, Model $record): bool
    {
        return $user->isNational();
    }

    public function create(User $user): bool
    {
        return $user->isNational();
    }

    public function update(User $user, Model $record): bool
    {
        return $user->isNational();
    }

    public function delete(User $user, Model $record): bool
    {
        return $user->isNational();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isNational();
    }

    public function restore(User $user, Model $record): bool
    {
        return $user->isNational();
    }

    public function forceDelete(User $user, Model $record): bool
    {
        return $user->isNational();
    }
}
