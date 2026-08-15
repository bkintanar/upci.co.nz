<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

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
            return $attendance->church?->region_id === $user->region_id;
        }

        if ($user->isLocal()) {
            return $attendance->church_id === $user->church_id;
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
