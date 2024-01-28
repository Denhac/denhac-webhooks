<?php

namespace App\Providers;

use App\External\QuickBooks\QuickBooksAuthSettings;
use App\Http\Requests\SlackRequest;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
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

        Queue::after(function (JobProcessed $event) {
            # This is bound via singleton instead of scoped, so we need to forget it to get updated settings each job
            app()->forgetInstance('anlutro\LaravelSettings\SettingsManager');
        });
    }
}
