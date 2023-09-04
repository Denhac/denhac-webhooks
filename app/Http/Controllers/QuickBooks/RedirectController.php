<?php

namespace App\Http\Controllers\QuickBooks;

use App\External\QuickBooks\QuickBooksAuthSettings;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2LoginHelper;
use QuickBooksOnline\API\DataService\DataService;

class RedirectController extends Controller
{
    public function redirect(Request $request): Redirector|Application|RedirectResponse
    {
        if (QuickBooksAuthSettings::hasKnownAuth()) {
            return redirect('/quickbooks/fail');
        }

        $code = $request->query('code');
        $realmId = $request->query('realmId');

        /** @var DataService $dataService */
        $dataService = app(DataService::class);
        /** @var OAuth2LoginHelper $authLoginHelper */
        $authLoginHelper = app(OAuth2LoginHelper::class);

        $accessToken = $authLoginHelper->exchangeAuthorizationCodeForToken($code, $realmId);
        $dataService->updateOAuth2Token($accessToken);

        QuickBooksAuthSettings::saveDataServiceInfo();

        return redirect('/quickbooks/success');
    }

    public function success(): View|Factory
    {
        return view('quickbooks.success');
    }

    public function fail(): View|Factory
    {
        return view('quickbooks.success');
    }
}
