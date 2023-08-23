<?php

namespace App\Providers;

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
            $dataServiceParameters = [
                'auth_mode' => 'oauth2',
                'ClientID' => config('denhac.quickbooks.client_id'),
                'ClientSecret' => config('denhac.quickbooks.client_secret'),
                'RedirectURI' => config('denhac.quickbooks.redirect'),
                'scope' => "com.intuit.quickbooks.accounting",
                'baseUrl' => "production"
            ];

            $accessToken = setting('quickbooks.accessToken');
            $refreshToken = setting('quickbooks.refreshToken');
            if (!is_null($accessToken)) {
                $dataServiceParameters['accessTokenKey'] = $accessToken;
            }

            if (!is_null($refreshToken)) {
                $dataServiceParameters['refreshTokenKey'] = $refreshToken;
            }

            return DataService::Configure($dataServiceParameters);
        });

        $this->app->singleton(OAuth2LoginHelper::class, function () {
            /** @var DataService $dataService */
            $dataService = app(DataService::class);
            $OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();

            try {
                // If this throws, we don't have a token to refresh.
                // I have not found a better way to do it.
                $OAuth2LoginHelper->getAccessToken();
            } catch(SdkException) {
                return $OAuth2LoginHelper;
            }

            $accessToken = $OAuth2LoginHelper->refreshToken();
            // TODO check $OAuth2LoginHelper->getLastError()

            $dataService->updateOAuth2Token($accessToken);

            setting([
                'quickbooks' => [
                    'accessToken' => $accessToken->getAccessToken(),
                    'refreshToken' => $accessToken->getRefreshToken(),
                ],
            ])->save();

            return $OAuth2LoginHelper;
        });
    }
}
