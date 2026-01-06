<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    // The path to the "home" route for your application
    public const HOME = '/';

    // Define your route model bindings, pattern filters, etc
    public function boot(): void
    {
        parent::boot();

        // Configure rate limiting
        $this->configureRateLimiting();

        // Route model bindings
        Route::bind('user', function ($value) {
            return \App\Models\Users\User::where('id', $value)->firstOrFail();
        });
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Default API rate limit: 60 requests per minute
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Strict rate limit for authentication endpoints: 5 attempts per minute
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Relaxed rate limit for authenticated users: 120 requests per minute
        RateLimiter::for('authenticated', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        // Relaxed rate limit for conversation: 400 requests per minute
        RateLimiter::for('conversation', function (Request $request) {
            return Limit::perMinute(250)->by($request->user()?->id ?: $request->ip());
        });

        // Admin endpoints: Higher limit - 200 requests per minute
        RateLimiter::for('admin', function (Request $request) {
            return Limit::perMinute(200)->by($request->user()?->id ?: $request->ip());
        });

        // File uploads: Lower limit - 10 uploads per minute
        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });
    }


}
