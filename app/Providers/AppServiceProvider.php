<?php

namespace App\Providers;

use App\Http\Requests\SlackRequest;
use Illuminate\Support\ServiceProvider;
use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2LoginHelper;
use QuickBooksOnline\API\DataService\DataService;
use Stripe\StripeClient;

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
        $this->app->resolving(SlackRequest::class, function ($request, $app) {
            return SlackRequest::createFrom($app['request'], $request);
        });

        $this->app->bind(StripeClient::class, function () {
            return new StripeClient(['api_key' => config('denhac.stripe.stripe_api_key')]);
        });

        $this->app->singleton(DataService::class, function() {
            return DataService::Configure(array(
                'auth_mode' => 'oauth2',
                'ClientID' => config('denhac.quickbooks.client_id'),
                'ClientSecret' =>  config('denhac.quickbooks.client_secret'),
                'RedirectURI' => route('quickbooks.redirect'),
                'scope' => "com.intuit.quickbooks.accounting",
                'baseUrl' => "production"
            ));
        });

        $this->app->singleton(OAuth2LoginHelper::class, function() {
            /** @var DataService $dataService */
            $dataService = app(DataService::class);
            return $dataService->getOAuth2LoginHelper();
        });
    }
}
