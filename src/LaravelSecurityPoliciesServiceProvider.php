<?php

namespace NagibMahfuj\LaravelSecurityPolicies;

use Illuminate\Support\ServiceProvider;

class LaravelSecurityPoliciesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/security-policies.php', 'security-policies');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/security-policies.php' => config_path('security-policies.php'),
        ], 'security-policies-config');

        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'security-policies-migrations');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/security-policies'),
        ], 'security-policies-views');

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'security-policies');
    }
}
