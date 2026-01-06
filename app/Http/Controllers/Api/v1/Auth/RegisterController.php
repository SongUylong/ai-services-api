<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\Users\UserResource;
use App\Services\Auth\AuthService;
use App\Services\Auth\OAuthService;
use App\Traits\Authentication;
use Illuminate\Support\Facades\DB;

// Auth Management - APIs for managing registration
class RegisterController extends ApiController
{
    use Authentication;

    public function __construct(
        protected AuthService $authService,
        protected OAuthService $oAuthService
    ) {}

    // Register a new user
    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        // Register the user first and commit the transaction
        // This ensures the user exists in the database before OAuth authentication
        $user = DB::transaction(function () use ($validated) {
            return $this->authService->register($validated);
        });

        // Refresh the user model to ensure we have the latest data from the database
        $user->refresh();

        // Now authenticate - the user is guaranteed to exist in the database
        $tokenResult = $this->oAuthService->authenticate(
            $validated['email'],
            $validated['password'],
            rememberMe: false
        );

        return $this->created([
            'user' => new UserResource($user),
            'token_type' => $tokenResult['token_type'],
            'expires_in' => $tokenResult['expires_in'],
            'access_token' => $tokenResult['access_token'],
            'refresh_token' => $tokenResult['refresh_token'],
            'rt_expires_in' => $tokenResult['rt_expires_in'],
            'remember_me' => false,
        ], 'User registered successfully')
            ->withCookie($this->makeAccessTokenCookie($tokenResult['access_token']))
            ->withCookie($this->makeRefreshTokenCookie($tokenResult, false));
    }
}
