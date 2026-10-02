<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Attendance;

class AttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->access_level;
    }

    public function view(User $user, Attendance $attendance): bool
    {
        return $user->canAccessChurch($attendance->church);
    }

    public function create(User $user): bool
    {
        return (bool) $user->access_level;
    }

    public function update(User $user, Attendance $attendance): bool
    {
        if ($user->isNational()) {
            return true;
        }

        if ($user->isRegional()) {
            // null === null would otherwise match an unscoped actor to an
            // orphaned row (no church) or a church with no region
            return $user->region_id !== null && $attendance->church?->region_id === $user->region_id;
        }

        if ($user->isLocal()) {
            return $user->church_id !== null && $attendance->church_id === $user->church_id;
        }

        return false;
    }

    public function delete(User $user, Attendance $attendance): bool
    {
        return $this->update($user, $attendance);
    }

    public function deleteAny(User $user): bool
    {
        return (bool) $user->access_level;
    }
}
