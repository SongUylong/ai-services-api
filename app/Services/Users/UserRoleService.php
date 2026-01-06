<?php

namespace App\Services\Users;

use App\Exceptions\ApiException;
use App\Models\Users\User;
use Illuminate\Http\Response;

class UserRoleService
{
    // Assign a role to a user
    public function assignRole(User $user, string $roleName): void
    {
        $user->assignRole($roleName);
    }

    // Remove a role from a user
    public function removeRole(User $user, string $roleName): void
    {
        if (! $user->hasRole($roleName)) {
            throw new ApiException(
                'User does not have the specified role',
                Response::HTTP_NOT_FOUND,
                'ROLE_NOT_FOUND'
            );
        }

        $user->removeRole($roleName);
    }

    // Sync user roles (replace all existing roles with new ones)
    public function syncRoles(User $user, array $roleNames): void
    {
        $roles = \App\Models\RolePermission\Role::whereIn('name', $roleNames)->get();
        $user->roles()->sync($roles->pluck('id'));
    }

    // Check if user has a specific role
    public function hasRole(User $user, string $roleName): bool
    {
        return $user->hasRole($roleName);
    }

    // Check if user has any of the given roles
    public function hasAnyRole(User $user, array $roles): bool
    {
        return $user->hasAnyRole($roles);
    }
}

