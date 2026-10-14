<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Department;

/**
 * Departments are national reference data: all authenticated admin users
 * can see the list. Writes are national-only.
 */
class DepartmentPolicy
{
    // National data: regional and local users see only their own region's
    // or church's data (owner, 2026-10-15). They could open departments
    // read-only before; the public site still shows every department.
    public function viewAny(User $user): bool
    {
        return $user->isNational();
    }

    public function view(User $user, Department $department): bool
    {
        return $user->isNational();
    }

    public function create(User $user): bool
    {
        return $user->isNational();
    }

    public function update(User $user, Department $department): bool
    {
        return $user->isNational();
    }

    public function delete(User $user, Department $department): bool
    {
        return $user->isNational();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isNational();
    }
}
