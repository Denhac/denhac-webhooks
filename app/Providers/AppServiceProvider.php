<?php

namespace App\Providers;

use App\External\QuickBooks\QuickBooksAuthSettings;
use App\Http\Requests\SlackRequest;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
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
    public function register(): void
    {
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->app->resolving(SlackRequest::class, function ($request, $app) {
            return SlackRequest::createFrom($app['request'], $request);
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

        RateLimiter::for('slack-profile-update', function () {
            // users.profile.set is tier 3, 50+ per minute.
            // users.profile.get is tier 4, 100+ per minute.
            // We probably won't update every user, but just to be on the safe side, we assume here that we do.
            return Limit::perMinute(50);
        });
    }
}
