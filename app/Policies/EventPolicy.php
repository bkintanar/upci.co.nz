<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Event;

/**
 * Events are visible to national and regional users only. Local users are
 * blocked at both the sidebar (via viewAny) and the URL (via the
 * NationalOrRegionalOnly route middleware on EventResource, which 404s
 * direct navigation). Creating / editing / deleting events is national-only.
 */
class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isNational() || $user->isRegional();
    }

    public function view(User $user, Event $event): bool
    {
        return $user->isNational() || $user->isRegional();
    }

    public function create(User $user): bool
    {
        return $user->isNational();
    }

    public function update(User $user, Event $event): bool
    {
        return $user->isNational();
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->isNational();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isNational();
    }
}
