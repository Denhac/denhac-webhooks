<?php

namespace App\Providers;

use App\Http\Requests\SlackSlashCommandRequest;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->resolving(SlackSlashCommandRequest::class, function ($request, $app) {
            return SlackSlashCommandRequest::createFrom($app['request'], $request);
        });
    }
}
