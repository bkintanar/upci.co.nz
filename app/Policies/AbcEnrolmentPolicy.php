<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * National-only, and read/delete only.
 *
 * An enrolment is an application someone else submitted, holding their date
 * of birth, address, phone numbers and any learning difficulties, so nobody
 * authors or rewrites one in the admin. The resource has no create or edit
 * page either; this makes that explicit at the authorization layer, the same
 * as ContactMessagePolicy.
 */
class AbcEnrolmentPolicy extends NationalOnlyPolicy
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
