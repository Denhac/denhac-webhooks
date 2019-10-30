<?php

namespace App\Providers;

use App\WooCommerce\Api\WooCommerceApi;
use Illuminate\Support\ServiceProvider;

class WooCommerceApiProvider extends ServiceProvider
{
    /**
     * Register services.
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
