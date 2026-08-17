<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Region;

/**
 * Mirrors ChurchPolicy's ownership shape: a regional user may edit the region
 * they belong to, but the set of regions itself is national-only.
 *
 * The reasoning is that content and structure are different privileges. A
 * presbyter writing their own region's intro, logo and name is routine content
 * work. Adding or removing a region reshapes the taxonomy that churches, users
 * and events are all keyed to — `users.region_id` and `churches.region_id`
 * both point here — so it stays with national.
 *
 * Note that Filament treats a model with NO policy as allowed, so this class
 * existing is itself part of the control. SecurityRegressionTest asserts it.
 */
class RegionPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->access_level;
    }

    public function view(User $user, Region $region): bool
    {
        if ($user->isNational()) {
            return true;
        }

        return $user->region_id === $region->id;
    }

    public function create(User $user): bool
    {
        return $user->isNational();
    }

    public function update(User $user, Region $region): bool
    {
        if ($user->isNational()) {
            return true;
        }

        // A regional user edits their own region's content and nobody else's.
        // Locals get nothing here: they are scoped to a church, and a church
        // does not own the region it sits in.
        if ($user->isRegional()) {
            return $user->region_id === $region->id;
        }

        return false;
    }

    public function delete(User $user, Region $region): bool
    {
        return $user->isNational();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isNational();
    }
}
