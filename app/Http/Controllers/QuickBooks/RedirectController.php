<?php

namespace App\Http\Controllers\QuickBooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2LoginHelper;
use QuickBooksOnline\API\DataService\DataService;

class RedirectController extends Controller
{
    public function __invoke(Request $request) {
        $code = $request->query('code');
        $realmId = $request->query('realmId');

        /** @var DataService $dataService */
        $dataService = app(DataService::class);
        /** @var OAuth2LoginHelper $authLoginHelper */
        $authLoginHelper = app(OAuth2LoginHelper::class);

        $accessToken = $authLoginHelper->exchangeAuthorizationCodeForToken($code, $realmId);
        $dataService->updateOAuth2Token($accessToken);

        dd($accessToken);
    }
}
