<?php

namespace NagibMahfuj\LaravelSecurityPolicies;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Routing\Router;
use NagibMahfuj\LaravelSecurityPolicies\Http\Middleware\IdleTimeoutMiddleware;
use NagibMahfuj\LaravelSecurityPolicies\Http\Middleware\RequireRecentMfaMiddleware;
use NagibMahfuj\LaravelSecurityPolicies\Http\Middleware\PasswordExpiredMiddleware;
use NagibMahfuj\LaravelSecurityPolicies\Listeners\OnPasswordReset;

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

        // Register middleware aliases for convenience
        $this->app->afterResolving('router', function (Router $router) {
            $router->aliasMiddleware('security.idle', IdleTimeoutMiddleware::class);
            $router->aliasMiddleware('security.mfa', RequireRecentMfaMiddleware::class);
            $router->aliasMiddleware('security.password_expired', PasswordExpiredMiddleware::class);
        });

        // Listen for password reset to record history and timestamp
        Event::listen(PasswordReset::class, [OnPasswordReset::class, 'handle']);
    }
}
