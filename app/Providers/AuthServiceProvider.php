<?php

namespace App\Providers;

use App\Services\Auth\OAuthService;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        \App\Models\Users\User::class => \App\Policies\Users\UserPolicy::class,
        \App\Models\Conversations\Conversation::class => \App\Policies\Conversations\ConversationPolicy::class,
        \App\Models\Messages\Message::class => \App\Policies\Messages\MessagePolicy::class,
        \App\Models\Messages\MessageFeedback::class => \App\Policies\Messages\MessageFeedbackPolicy::class,
    ];

    public function register(): void
    {
        $this->app->when(OAuthService::class)
            ->needs(Client::class)
            ->give(function () {
                $clientId = config('services.passport.password_client_id');
                $clientSecret = config('services.passport.password_client_secret');

                $client = Client::findOrFail($clientId);

                // Override the secret with the plain text version from config
                // The database stores the hashed version, but OAuth needs the plain version
                $client->secret = $clientSecret;

                return $client;
            });
    }

    public function boot(): void
    {
        Passport::enablePasswordGrant();
        Passport::tokensExpireIn(now()->addSeconds(config('passport.access_token_ttl')));
        Passport::refreshTokensExpireIn(now()->addSeconds(config('passport.refresh_token_ttl')));
    }
}
