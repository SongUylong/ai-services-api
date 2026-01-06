<?php

namespace App\Services\Auth;

use App\Exceptions\ApiException;
use App\Exceptions\ErrorCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\Client;

/**
 * OAuth service for password and refresh token grants
 */
class OAuthService
{
    public function __construct(
        private Client $oAuthClient
    ) {}

    /**
     * Authenticate user with email and password
     */
    public function authenticate(
        string $email,
        string $password,
        bool $rememberMe = false
    ): array {
        $oAuthResponse = $this->passwordGrant($email, $password);
        $oAuthResponse['rt_expires_in'] = $rememberMe
            ? config('passport.remember_me_refresh_token_ttl')
            : config('passport.refresh_token_ttl');
        $oAuthResponse['remember_me'] = $rememberMe;

        return $oAuthResponse;
    }

    /**
     * Refresh access token using refresh token
     */
    public function refreshToken(
        string $refreshToken,
        bool $rememberMe = false
    ): array {
        $oAuthResponse = $this->refreshTokenGrant($refreshToken);
        $oAuthResponse['rt_expires_in'] = $rememberMe
            ? config('passport.remember_me_refresh_token_ttl')
            : config('passport.refresh_token_ttl');
        $oAuthResponse['remember_me'] = $rememberMe;

        return $oAuthResponse;
    }

    /**
     * Get OAuth token via password grant
     */
    private function passwordGrant(string $email, string $password): array
    {
        $clientSecret = $this->oAuthClient->plainSecret ;

        $data = [
            'grant_type' => 'password',
            'client_id' => $this->oAuthClient->id,
            'client_secret' => $clientSecret,
            'username' => $email,
            'password' => $password,
            'scope' => '*',
        ];

        $request = Request::create('/oauth/token', 'POST', $data, [], [], [
            'HTTP_Accept' => 'application/json',
        ]);

        $response = app()->handle($request);
        $content = json_decode($response->getContent(), true);

        if (isset($content['error'])) {
            Log::error('OAuth password grant failed', [
                'error' => $content['error'],
                'error_description' => $content['error_description'] ?? null,
                'message' => $content['message'] ?? null,
                'hint' => $content['hint'] ?? null,
                'full_response' => $content,
            ]);

            throw new ApiException(
                $content['message'] ?? $content['error_description'] ?? 'Invalid credentials',
                401,
                ErrorCode::AUTHENTICATION_FAILED
            );
        }

        // Add explicit TTL values from config
        $content['expires_in'] = config('passport.access_token_ttl');

        return $content;
    }

    /**
     * Get new access token via refresh token grant
     */
    private function refreshTokenGrant(string $refreshToken): array
    {
        // Use plainSecret which contains the unencrypted secret set by AuthServiceProvider
        // The secret attribute is automatically hashed by Passport's Client model
        $clientSecret = $this->oAuthClient->plainSecret ?? config('services.passport.password_client_secret');

        $data = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $this->oAuthClient->id,
            'client_secret' => $clientSecret,
            'scope' => '*',
        ];

        $request = Request::create('/oauth/token', 'POST', $data, [], [], [
            'HTTP_Accept' => 'application/json',
        ]);

        $response = app()->handle($request);
        $content = json_decode($response->getContent(), true);

        if (isset($content['error'])) {
            Log::error('OAuth refresh token grant failed', [
                'error' => $content['error'],
                'message' => $content['message'] ?? null,
            ]);

            throw new ApiException(
                'Invalid refresh token',
                401,
                ErrorCode::INVALID_REFRESH_TOKEN
            );
        }

        // Add explicit TTL values from config
        $content['expires_in'] = config('passport.access_token_ttl');

        return $content;
    }
}
