<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Services\Auth\OAuthService;
use App\Traits\Authentication;

// Auth Management - APIs for managing token refresh
class RefreshTokenController extends ApiController
{
	use Authentication;

	public function __construct(
		protected OAuthService $oAuthService
	) {}

	// Refresh the access token using OAuth service
	public function __invoke(RefreshTokenRequest $request)
	{
		$rememberMe = $request->boolean('remember_me');
		$refreshToken = $request->input('refresh_token') ?? $request->cookie('refresh_token');

		// Use OAuth service to refresh token
		$tokenResult = $this->oAuthService->refreshToken($refreshToken, $rememberMe);

		return $this->okWithData([
			'token_type' => $tokenResult['token_type'],
			'expires_in' => $tokenResult['expires_in'],
			'access_token' => $tokenResult['access_token'],
			'refresh_token' => $tokenResult['refresh_token'],
			'rt_expires_in' => $tokenResult['rt_expires_in'],
			'remember_me' => $rememberMe,
		], 'Token refreshed successfully')
			->withCookie($this->makeRMCookie($rememberMe))
			->withCookie($this->makeAccessTokenCookie($tokenResult['access_token']))
			->withCookie($this->makeRefreshTokenCookie($tokenResult, $rememberMe));
	}
}

