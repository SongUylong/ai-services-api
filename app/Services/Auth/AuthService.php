<?php

namespace App\Services\Auth;

use App\Models\Users\User;

// Main authentication service that orchestrates other auth services
class AuthService
{
    public function __construct(
        public RegistrationService $registrationService,
        public CredentialService $credentialService,
        public TokenService $tokenService
    ) {}

    // Register a new user
    public function register(array $data): User
    {
        return $this->registrationService->register($data);
    }

    // Validate user credentials (accepts email or username)
    public function validateCredentials(string $emailOrUsername, string $password): User
    {
        return $this->credentialService->validateCredentials($emailOrUsername, $password);
    }

    // Logout user (revoke current token)
    public function logout(User $user): void
    {
        $this->tokenService->revokeAllTokens($user);
    }

    // Get authenticated user with roles and permissions
    public function getAuthenticatedUser(User $user): User
    {
        return $this->tokenService->getAuthenticatedUser($user);
    }
}
