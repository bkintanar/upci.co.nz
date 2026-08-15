<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;

/**
 * Departments are national reference data: all authenticated admin users
 * can see the list. Writes are national-only.
 */
class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->access_level;
    }

    public function view(User $user, Department $department): bool
    {
        return (bool) $user->access_level;
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
