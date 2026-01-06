<?php

namespace App\Services\Auth;

use App\Models\Users\User;
use Illuminate\Support\Facades\DB;

class TokenService
{
    /**
     * Revoke a specific token
     */
    public function revokeToken(User $user, int|string $tokenId): void
    {
        $token = $user->tokens()->find($tokenId);
        
        if ($token) {
            $token->revoke();
            
            // Revoke refresh tokens tied to this access token
            DB::table('oauth_refresh_tokens')
                ->where('access_token_id', $tokenId)
                ->update(['revoked' => true]);
        }
    }

    /**
     * Revoke all tokens for a user
     */
    public function revokeAllTokens(User $user): void
    {
        $user->tokens->each(function ($token) {
            $token->revoke();

            DB::table('oauth_refresh_tokens')
                ->where('access_token_id', $token->id)
                ->update(['revoked' => true]);
        });
    }

    /**
     * Get authenticated user with roles
     */
    public function getAuthenticatedUser(User $user): User
    {
        return $user->load('roles');
    }
}

