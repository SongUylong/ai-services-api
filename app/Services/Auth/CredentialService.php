<?php

namespace App\Services\Auth;

use App\Helpers\Auth\PasswordHelper;
use App\Models\Users\User;
use App\Repositories\UserRepository;
use Illuminate\Validation\ValidationException;

class CredentialService
{
    public function __construct(
        protected UserRepository $userRepository
    ) {}

    // Validate user credentials (accepts email or username)
    public function validateCredentials(string $emailOrUsername, string $password): User
    {
        // Try to find user by email first, then by username
        $user = $this->userRepository->findByEmail($emailOrUsername);

        if (!$user) {
            $user = $this->userRepository->findByUsername($emailOrUsername);
        }

        if (!$user || !PasswordHelper::verify($password, $user->password)) {
            throw ValidationException::withMessages([
                'username_or_email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'username_or_email' => ['Your account is inactive.'],
            ]);
        }

        // Update last login
        $this->userRepository->update($user, ['last_login' => now()]);

        return $user;
    }
}
