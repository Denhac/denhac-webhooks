<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders()
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        // api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        // channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(RouteServiceProvider::HOME);

        $middleware->validateCsrfTokens(except: [
            'webhooks/denhac-org',
            'webhooks/octoprint',
            'webhooks/waiver',
            'horizon/*',
        ]);

        $middleware->throttleApi('600,1');

        $middleware->replace(\Illuminate\Http\Middleware\TrustProxies::class, \App\Http\Middleware\TrustProxies::class);

        $middleware->alias([
            'feature' => \YlsIdeas\FeatureFlags\Middleware\FeatureFlagState::class,
            'scope' => \Laravel\Passport\Http\Middleware\CheckForAnyScope::class,
            'scopes' => \Laravel\Passport\Http\Middleware\CheckScopes::class,
            'slack' => \App\Http\Middleware\AuthorizeSlackRequest::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
