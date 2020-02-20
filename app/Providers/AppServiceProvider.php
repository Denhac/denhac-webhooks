<?php

namespace App\Providers;

use App\Github\TokenManager as GithubTokenManager;
use App\Google\TokenManager as GoogleTokenManager;
use App\Slack\SlackApi;
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

        $this->app->bind(GoogleTokenManager::class, function () {
            return new GoogleTokenManager(
                file_get_contents(config('denhac.google.key_path')),
                config('denhac.google.service_account'),
                config('denhac.google.auth_as')
            );
        });

        $this->app->bind(SlackApi::class, function() {
            return new SlackApi(
                config('denhac.slack.api_token'),
                config('denhac.slack.email'),
                config('denhac.slack.password')
            );
        });

        $this->app->bind(GithubTokenManager::class, function () {
            return new GithubTokenManager(
                file_get_contents(config('denhac.github.key_path')),
                config('denhac.github.app_id'),
                config('denhac.github.installation_id')
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
