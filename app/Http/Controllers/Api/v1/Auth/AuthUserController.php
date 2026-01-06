<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\Users\UserResource;
use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\Gate;

// Auth Management - APIs for managing authenticated user information
class AuthUserController extends ApiController
{
    public function __construct(
        protected AuthService $authService
    ) {}

    // Get authenticated user
    public function show()
    {
        // Use the API guard (Passport) explicitly; the default guard may be "web"
        // and would return null even for a valid API token.
        $user = $this->authService->getAuthenticatedUser(auth('api')->user());

        // Policy check: users can only view their own info (or admin can view any)
        Gate::forUser($user)->authorize('viewOwnUser', $user);

        return $this->okWithData([
            'user' => new UserResource($user),
        ]);
    }
}
