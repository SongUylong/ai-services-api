<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\Users\UserResource;
use App\Services\Auth\AuthService;
use App\Services\Auth\OAuthService;
use App\Services\Users\UserSettingService;
use App\Traits\Authentication;

// Auth Management - APIs for managing login
class LoginController extends ApiController
{
    use Authentication;

    public function __construct(
        protected AuthService $authService,
        protected OAuthService $oAuthService,
        protected UserSettingService $userSettingService
    ) {}

    // Login user and create OAuth token
    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        // Validate user credentials first
        $user = $this->authService->validateCredentials(
            $validated['username_or_email'],
            $validated['password']
        );

        // Get remember_me preference
        $rememberMe = $validated['remember_me'] ?? $this->userSettingService->getSetting($user, 'remember_me', false);

        // Save remember_me preference if provided
        if (isset($validated['remember_me'])) {
            $this->userSettingService->updateSetting($user, 'remember_me', $rememberMe);
        }

        // Use OAuth service to get tokens
        $tokenResult = $this->oAuthService->authenticate(
            $user->email,
            $validated['password'],
            $rememberMe
        );

        // Load user relationships
        $user->loadMissing('roles');

        return $this->okWithData([
            'token_type' => $tokenResult['token_type'],
            'expires_in' => $tokenResult['expires_in'],
            'access_token' => $tokenResult['access_token'],
            'refresh_token' => $tokenResult['refresh_token'],
            'rt_expires_in' => $tokenResult['rt_expires_in'],
            'remember_me' => $rememberMe,
            'user' => new UserResource($user),
        ], 'Login successful')
            ->withCookie($this->makeRMCookie($rememberMe))
            ->withCookie($this->makeAccessTokenCookie($tokenResult['access_token']))
            ->withCookie($this->makeRefreshTokenCookie($tokenResult, $rememberMe));
    }
}
