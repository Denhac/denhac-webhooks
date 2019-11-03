<?php

namespace App\Providers;

use App\Google\TokenManager;
use App\WooCommerce\Api\WooCommerceApi;
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
        $this->app->bind(WooCommerceApi::class, function () {
            return new WooCommerceApi(
                config('denhac.url'),
                config('denhac.rest.key'),
                config('denhac.rest.secret')
            );
        });

        $this->app->bind(TokenManager::class, function () {
            return new TokenManager(
                file_get_contents(config('denhac.google.key_path')),
                config('denhac.google.service_account'),
                config('denhac.google.auth_as')
            );
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
