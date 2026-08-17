<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * National-only, and read/delete only.
 *
 * NationalOnlyPolicy grants create and update, which is wrong for an inbound
 * message: nobody should be authoring or rewriting something a member of the
 * public sent. The resource has no create or edit page either — this makes
 * that explicit at the authorization layer rather than relying on the absence
 * of a route.
 */
class ContactMessagePolicy extends NationalOnlyPolicy
{
    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Model $record): bool
    {
        return false;
    }
}
