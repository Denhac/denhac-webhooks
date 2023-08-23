<?php

namespace App\Providers;

use App\External\QuickBooks\QuickBooksAuthSettings;
use App\Http\Requests\SlackRequest;
use Illuminate\Support\ServiceProvider;
use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2LoginHelper;
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\Exception\SdkException;
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

        $this->app->singleton(DataService::class, function () {
            return DataService::Configure(QuickBooksAuthSettings::getDataServiceParameters());
        });

        $this->app->singleton(OAuth2LoginHelper::class, function () {
            /** @var DataService $dataService */
            $dataService = app(DataService::class);
            $OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();

            try {
                // If this throws, we don't have a token to refresh.
                // I have not found a better way to do it.
                $OAuth2LoginHelper->getAccessToken();
            } catch (SdkException) {
                return $OAuth2LoginHelper;
            }

            $accessToken = $OAuth2LoginHelper->refreshToken();
            // TODO check $OAuth2LoginHelper->getLastError()

            $dataService->updateOAuth2Token($accessToken);

            QuickBooksAuthSettings::saveDataServiceInfo();

            return $OAuth2LoginHelper;
        });
    }
}
