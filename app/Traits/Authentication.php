<?php

namespace App\Traits;

trait Authentication
{
    /**
     * Create refresh token cookie
     */
    public function makeRefreshTokenCookie(array $response, bool $remember = false)
    {
        $minutes = $remember ? 365 * 24 * 60 : 24 * 60;
        return $this->makeCookie('refresh_token', $response['refresh_token'], $minutes);
    }

    /**
     * Create access token cookie
     */
    public function makeAccessTokenCookie(string $accessToken)
    {
        return $this->makeCookie('access_token', $accessToken, 3 * 60);
    }

    /**
     * Create remember me cookie
     */
    public function makeRMCookie(bool $remember)
    {
        $minutes = $remember ? 365 * 24 * 60 : 24 * 60;
        return $this->makeCookie('remember_me', $remember ? '1' : '0', $minutes);
    }

    /**
     * Delete refresh token cookie
     */
    public function deleteRefreshTokenCookie()
    {
        return $this->makeCookie('refresh_token', null, 0);
    }

    /**
     * Delete access token cookie
     */
    public function deleteAccessTokenCookie()
    {
        return $this->makeCookie('access_token', null, 0);
    }

    /**
     * Create secure HTTP-only cookie
     */
    private function makeCookie(string $key, ?string $value, int $minutes)
    {
        return cookie(
            $key,
            $value,
            $minutes,
            '/',
            config('app.domain', null),
            true, // secure
            true, // httpOnly
            false, // raw
            'Strict' // sameSite
        );
    }
}
