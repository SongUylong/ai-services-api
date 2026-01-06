<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Route middleware aliases
        $middleware->alias([
            // Protects API routes by requiring a valid authenticated user (via Passport "api" guard)
            'auth.api' => \App\Http\Middleware\AuthenticateApi::class,
        ]);
        
        // Enable CORS for frontend
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
        
        // Add rate limiting and token expiration check to API routes
        $middleware->api(append: [
            'throttle:api', // Rate limiting: 60 requests per minute by default
            \App\Http\Middleware\CheckTokenExpiration::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return app(\App\Exceptions\Handler::class)->renderJsonException($request, $e);
            }
        });
    })->create();
