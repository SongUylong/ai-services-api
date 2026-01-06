<?php

namespace App\Policies\Users;

use App\Models\Users\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    // VIEW ANY: Check permission for viewing user list
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view any user');
    }

    // VIEW: Check permission for viewing specific user
    public function view(User $user, ?User $model = null): bool
    {
        // User with permission can view any user
        if ($user->hasPermissionTo('view any user')) {
            return true;
        }

        // Users can view their own profile
        return $model && $user->id === $model->id && $user->hasPermissionTo('view own profile');
    }

    // CREATE: Check permission for creating users
    public function create(?User $user): bool
    {
        return $user && $user->hasPermissionTo('create user');
    }

    // UPDATE: Check permission for updating users
    public function update(User $user, ?User $model = null): bool
    {
        // User with permission can update any user
        if ($user->hasPermissionTo('update any user')) {
            return true;
        }

        // Users with permission can update their own profile
        return $model && $user->id === $model->id && $user->hasPermissionTo('update own profile');
    }

    // UPDATE OWN: User updating their own profile
    public function updateOwn(User $user): bool
    {
        // User with permission can update any profile (including their own)
        if ($user->hasPermissionTo('update any user')) {
            return true;
        }

        // Users with permission can update their own profile
        return $user->hasPermissionTo('update own profile');
    }

    // DELETE: Check permission for deleting users
    public function delete(User $user, ?User $model = null): bool
    {
        return $user->hasPermissionTo('delete any user');
    }

    // TOGGLE STATUS: Check permission for activating/deactivating users
    public function toggleStatus(User $user): bool
    {
        return $user->hasPermissionTo('toggle user status');
    }

    // VIEW SETTINGS: Check permission for viewing settings
    public function viewSettings(User $user, User $model): bool
    {
        // User with permission can view any settings
        if ($user->hasPermissionTo('view any settings')) {
            return true;
        }

        // Users with permission can view their own settings
        return $user->id === $model->id && $user->hasPermissionTo('view own settings');
    }

    // UPDATE SETTINGS: Check permission for updating settings
    public function updateSettings(User $user, User $model): bool
    {
        // User with permission can update any settings
        if ($user->hasPermissionTo('update any settings')) {
            return true;
        }

        // Users with permission can update their own settings
        return $user->id === $model->id && $user->hasPermissionTo('update own settings');
    }

    // UPDATE USERNAME: Check permission for changing username
    public function updateUsername(User $user, User $model): bool
    {
        // User with permission can change any username
        if ($user->hasPermissionTo('change any username')) {
            return true;
        }

        // Users with permission can change their own username
        return $user->id === $model->id && $user->hasPermissionTo('change own username');
    }

    // UPDATE PASSWORD: Check permission for changing password
    public function updatePassword(User $user, User $model): bool
    {
        // User with permission can change any password
        if ($user->hasPermissionTo('change any password')) {
            return true;
        }

        // Users with permission can change their own password
        return $user->id === $model->id && $user->hasPermissionTo('change own password');
    }

    // VIEW ROLES: Check permission for viewing user roles
    public function viewRoles(User $user): bool
    {
        return $user->hasPermissionTo('view roles');
    }

    // ASSIGN ROLES: Check permission for assigning roles
    public function assignRoles(User $user): bool
    {
        return $user->hasPermissionTo('assign roles');
    }

    // REMOVE ROLES: Check permission for removing roles
    public function removeRoles(User $user): bool
    {
        return $user->hasPermissionTo('remove roles');
    }

    // UPLOAD IMAGE: Check permission for uploading profile image
    public function uploadImage(User $user, User $model): bool
    {
        // User with permission can upload for anyone
        if ($user->hasPermissionTo('upload profile image')) {
            return true;
        }

        // Users can upload their own profile image
        return $user->id === $model->id && $user->hasPermissionTo('upload own profile image');
    }

    // VIEW OWN USER: For /me routes
    public function viewOwnUser(User $user, ?User $model = null): bool
    {
        if ($model) {
            return $user->id === $model->id;
        }

        return true; // For /me route without model
    }
}
