<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Satusehat\Integration\OAuth2Client;

class SatusehatServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Bind the OAuth2Client to the container
        $this->app->singleton(OAuth2Client::class, function ($app) {
            // We don't register it here with credentials since those are clinic-specific
            // Instead, we'll let individual services handle their own OAuth2Client instantiation
            return new OAuth2Client();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}