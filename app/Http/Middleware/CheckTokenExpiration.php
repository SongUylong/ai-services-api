<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Middleware to check if the access token has expired
class CheckTokenExpiration
{
    // Handle an incoming request
    public function handle(Request $request, Closure $next): Response
    {
        // Only check if user is authenticated via API guard
        if ($request->user('api')) {
            $token = $request->user('api')->token();
            
            // Check if token has expired
            if ($token && $token->expires_at && $token->expires_at->isPast()) {
                return response()->json([
                    'message' => 'Token has expired',
                    'error' => 'token_expired',
                ], 401);
            }
        }
        
        return $next($request);
    }
}

