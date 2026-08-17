<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Region;
use App\Models\GalleryItem;

/**
 * National users manage every gallery. A regional user manages their own
 * region's gallery and nothing else.
 *
 * This follows from RegionPolicy: a presbyter who may write their region's
 * intro and logo is already trusted with that region's content, and the
 * gallery relation manager on RegionResource would otherwise render a tab
 * they cannot use. Department and general galleries stay national, since no
 * user is scoped to a department.
 *
 * Deliberately NOT extending NationalOnlyPolicy — the ownership check is the
 * whole point, and inheriting then overriding half the methods hides that.
 */
class GalleryItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isNational() || $user->isRegional();
    }

    public function view(User $user, GalleryItem $item): bool
    {
        return $this->manages($user, $item);
    }

    public function create(User $user): bool
    {
        // The owner is decided by the record being edited, so a regional user
        // reaching create has already passed the region's own gate.
        return $user->isNational() || $user->isRegional();
    }

    public function update(User $user, GalleryItem $item): bool
    {
        return $this->manages($user, $item);
    }

    public function delete(User $user, GalleryItem $item): bool
    {
        return $this->manages($user, $item);
    }

    public function deleteAny(User $user): bool
    {
        return $user->isNational();
    }

    public function restore(User $user, GalleryItem $item): bool
    {
        return $this->manages($user, $item);
    }

    public function forceDelete(User $user, GalleryItem $item): bool
    {
        return $user->isNational();
    }

    /**
     * An item is a regional user's only when it is owned by a Region and that
     * region is theirs. A null owner is the general gallery, which is national.
     */
    private function manages(User $user, GalleryItem $item): bool
    {
        if ($user->isNational()) {
            return true;
        }

        if (! $user->isRegional() || $item->galleryable_type !== (new Region)->getMorphClass()) {
            return false;
        }

        return $item->galleryable_id === $user->region_id;
    }
}
