<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApi
{
    // Ensure the request has a valid authenticated API user
    public function handle(Request $request, Closure $next): Response
    {
		// Force the 'api' guard for the current request so policies use the token user
		Auth::shouldUse('api');

        if (!auth('api')->check()) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        return $next($request);
    }
}


