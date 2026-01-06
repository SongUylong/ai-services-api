<?php

namespace App\Http\Controllers\Api\v1\Users;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Users\ChangePasswordRequest;
use App\Models\Users\User;
use App\Services\Users\UserPasswordService;

// Auth Management - APIs for managing password changes
class ChangePasswordController extends ApiController
{
    public function __construct(
        protected UserPasswordService $userPasswordService
    ) {}

    // Change a user password
    public function update(ChangePasswordRequest $request, User $user)
    {
        // For self-service route (/v1/user/password) there is no {user} param.
        // Default to the authenticated user when the route model is missing.
        if (!$user->exists) {
            $user = auth('api')->user();
        }

        // Check if user can change password (own password or admin with permission)
        if (auth('api')->id() !== $user->id) {
            $this->authorize('updatePassword', $user);
        }

        $canChangeWithoutOldPassword = auth('api')->id() !== $user->id;
        $validated = $request->validated();

        // Change password using service
        $this->userPasswordService->changePassword(
            $user,
            $validated['password'],
            $validated['current_password'] ?? null,
            !$canChangeWithoutOldPassword
        );

        return $this->okWithMsg('Password changed successfully');
    }
}
