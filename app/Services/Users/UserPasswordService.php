<?php

namespace App\Services\Users;

use App\Helpers\Auth\PasswordHelper;
use App\Models\Users\User;

class UserPasswordService
{
    // Change user password
    public function changePassword(
        User $user,
        string $newPassword,
        ?string $currentPassword = null,
        bool $requireCurrentPassword = true
    ): void {
        // Verify current password if required
        if ($requireCurrentPassword && $currentPassword) {
            PasswordHelper::validatePassword($currentPassword, $user->password, 'password');
        }

        // Check if new password is different from old password
        // Pass currentPassword to enable Levenshtein distance check (min 5 characters different)
        PasswordHelper::ensurePasswordChanged($newPassword, $user->password, $currentPassword);

        // Update password
        $user->password = PasswordHelper::hash($newPassword);
        $user->last_password_change = now();
        $user->save();
    }

    // Reset user password (admin action - no current password required)
    public function resetPassword(User $user, string $newPassword): void
    {
        $this->changePassword($user, $newPassword, null, false);
    }
}

