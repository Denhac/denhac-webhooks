<?php

namespace App\Console\Commands\QuickBooks;

use App\External\QuickBooks\QuickBooksAuthSettings;
use Illuminate\Console\Command;
use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2LoginHelper;
use QuickBooksOnline\API\DataService\DataService;

class RefreshAccessToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quickbooks:refresh-access-token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refreshes the access token that expires hourly with QuickBooks';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        if (! QuickBooksAuthSettings::hasKnownAuth()) {
            return;
        }
        /** @var DataService $dataService */
        $dataService = app(DataService::class);
        /** @var OAuth2LoginHelper $OAuth2LoginHelper */
        $OAuth2LoginHelper = app(OAuth2LoginHelper::class);

        // If this throws, we don't have a token to refresh.
        // I have not found a better way to do it.
        $OAuth2LoginHelper->getAccessToken();

        $accessToken = $OAuth2LoginHelper->refreshToken();
        // TODO check $OAuth2LoginHelper->getLastError() or if refreshToken just throws for us

        $dataService->updateOAuth2Token($accessToken);

        QuickBooksAuthSettings::saveDataServiceInfo($accessToken);
    }
}
