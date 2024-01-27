<?php

namespace App\Providers;

use App\External\QuickBooks\QuickBooksAuthSettings;
use App\Http\Requests\SlackRequest;
use App\VolunteerGroupChannels\ChannelInterface;
use Illuminate\Support\ServiceProvider;
use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2LoginHelper;
use QuickBooksOnline\API\DataService\DataService;
use Stripe\StripeClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->resolving(SlackRequest::class, function ($request, $app) {
            return SlackRequest::createFrom($app['request'], $request);
        });

        // Only create one instance of each implementation per request cycle
        $this->app->afterResolving(ChannelInterface::class, function ($resolved, $app) {
            $app->instance(get_class($resolved), $resolved);
        });

        $this->app->bind(StripeClient::class, function () {
            return new StripeClient(['api_key' => config('denhac.stripe.stripe_api_key')]);
        });

        $this->app->bind(DataService::class, function () {
            $dataService = DataService::Configure(QuickBooksAuthSettings::getDataServiceParameters());
            $dataService->setMinorVersion(53);  // So we can auto assign DocNumber's for journal entries

            return $dataService;
        });

        $this->app->bind(OAuth2LoginHelper::class, function () {
            /** @var DataService $dataService */
            $dataService = app(DataService::class);

            return $dataService->getOAuth2LoginHelper();
        });
    }
}
