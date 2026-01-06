<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Auth\LogoutAllDevicesRequest;
use App\Services\Auth\TokenService;
use App\Traits\Authentication;

// Auth Management - APIs for managing logout
class LogoutController extends ApiController
{
	use Authentication;

	public function __construct(
		protected TokenService $tokenService
	) {}

	// Logout user (revoke current token)
	public function logout()
	{
		$user = request()->user();
		$token = $user->token();
		
		// Revoke token using service
		$this->tokenService->revokeToken($user, $token->id);

		return $this->okWithMsg('Logged out successfully')
			->withCookie($this->deleteAccessTokenCookie())
			->withCookie($this->deleteRefreshTokenCookie());
	}

	// Logout from all devices
	public function logoutAllDevices(LogoutAllDevicesRequest $request)
	{
		$user = $request->user();

		// Revoke all tokens using service
		$this->tokenService->revokeAllTokens($user);

		return $this->okWithMsg('Logged out from all devices successfully');
	}
}

