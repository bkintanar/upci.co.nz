<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Read/delete only.
 *
 * An enrolment is an application someone else submitted, holding their date
 * of birth, address, phone numbers and any learning difficulties, so nobody
 * authors or rewrites one in the admin. The resource has no create or edit
 * page either; this makes that explicit at the authorization layer, the same
 * as ContactMessagePolicy.
 *
 * National users and Bible School logins may read enrolments and ask a pastor
 * for a reference. Deleting one stays national-only (inherited).
 */
class AbcEnrolmentPolicy extends NationalOnlyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isNational() || $user->isBibleSchool();
    }

    public function view(User $user, Model $record): bool
    {
        return $this->viewAny($user);
    }

    /**
     * ViewAbcEnrolment's "Ask the pastor for a reference" / "Email the pastor
     * again". It sends email in the college's name.
     */
    public function emailPastor(User $user, Model $record): bool
    {
        return $this->view($user, $record);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Model $record): bool
    {
        return false;
    }
}
