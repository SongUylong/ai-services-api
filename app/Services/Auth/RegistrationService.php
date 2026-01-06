<?php

namespace App\Services\Auth;

use App\Helpers\Auth\PasswordHelper;
use App\Models\Users\User;
use App\Repositories\UserRepository;

class RegistrationService
{
    public function __construct(
        protected UserRepository $userRepository
    ) {}

    // Register a new user
    public function register(array $data): User
    {
        // Generate unique username
        $username = $this->userRepository->generateUniqueUsername(
            $data['first_name'],
            $data['last_name'],
            $data['email']
        );

        // Create user
        $user = $this->userRepository->create([
            'username' => $username,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'] ?? null,
            'password' => PasswordHelper::hash($data['password']),
            'is_active' => true,
            'last_password_change' => now(),
        ]);

        // Assign default user role
        $user->assignRole('user');

        // Create default user settings
        $user->setting()->create(\App\Models\Users\UserSetting::defaults());

        // Load basic role information (no permissions needed; policies decide access)
        return $user->load('roles');
    }
}
