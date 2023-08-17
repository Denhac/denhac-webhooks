<?php

namespace App\Http\Controllers\QuickBooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2LoginHelper;

class LaunchController extends Controller
{
    public function __invoke()
    {
        /** @var OAuth2LoginHelper $authLoginHelper */
        $authLoginHelper = app(OAuth2LoginHelper::class);
        return view('quickbooks.launch', [
            'auth_url' => $authLoginHelper->getAuthorizationCodeURL(),
        ]);
    }
}
