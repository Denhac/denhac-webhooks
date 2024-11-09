<?php

namespace App\Providers;

use App\External\QuickBooks\QuickBooksAuthSettings;
use App\Http\Requests\SlackRequest;
use App\Models\CardUpdateRequest;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2LoginHelper;
use QuickBooksOnline\API\DataService\DataService;
use Stripe\StripeClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Register any application services.
     */
    public function register(): void {}

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
            // This is bound via singleton instead of scoped, so we need to forget it to get updated settings each job
            app()->forgetInstance('anlutro\LaravelSettings\SettingsManager');
        });

        $this->bootAuth();
        $this->bootBroadcast();
        $this->bootRoute();
    }

    public function bootAuth(): void
    {
        Passport::tokensCan([
            'card:manage' => 'Manage card access', // Used by WinDSX only
            'door:manage' => 'Manage door control', // Used by the Pi that controls doors for open house
        ]);
    }

    public function bootBroadcast(): void
    {
        Broadcast::routes([
            'middleware' => ['auth:api'],
        ]);
    }

    public function bootRoute(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        Route::model('card_update_request', CardUpdateRequest::class);


    }
}
