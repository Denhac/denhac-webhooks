<?php

namespace App\External\QuickBooks;


use anlutro\LaravelSettings\Facades\Setting;
use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2LoginHelper;

class QuickBooksAuthSettings
{
    protected const ACCESS_TOKEN_KEY = "quickbooks.auth.accessToken";
    protected const REFRESH_TOKEN_KEY = "quickbooks.auth.refreshToken";
    protected const REALM_ID_KEY = "quickbooks.auth.realmId";

    public static function hasKnownAuth(): bool {
        return !is_null(setting(self::ACCESS_TOKEN_KEY)) ||
            !is_null(setting(self::REFRESH_TOKEN_KEY)) ||
            !is_null(setting(self::REALM_ID_KEY));
    }

    public static function getDataServiceParameters(): array
    {
        $dataServiceParameters = [
            'auth_mode' => 'oauth2',
            'ClientID' => config('denhac.quickbooks.client_id'),
            'ClientSecret' => config('denhac.quickbooks.client_secret'),
            'RedirectURI' => config('denhac.quickbooks.redirect'),
            'scope' => "com.intuit.quickbooks.accounting",
            'baseUrl' => config('denhac.quickbooks.base_url'),
        ];

        $accessToken = setting(self::ACCESS_TOKEN_KEY);
        if (!is_null($accessToken)) {
            $dataServiceParameters['accessTokenKey'] = $accessToken;
        }

        $refreshToken = setting(self::REFRESH_TOKEN_KEY);
        if (!is_null($refreshToken)) {
            $dataServiceParameters['refreshTokenKey'] = $refreshToken;
        }

        $realmId = setting(self::REALM_ID_KEY);
        if (!is_null($realmId)) {
            $dataServiceParameters['realmId'] = $realmId;
        }

        return $dataServiceParameters;
    }

    public static function saveDataServiceInfo(): void
    {
        /** @var OAuth2LoginHelper $OAuth2LoginHelper */
        $OAuth2LoginHelper = app(OAuth2LoginHelper::class);
        $accessToken = $OAuth2LoginHelper->getAccessToken();

        setting([
            self::ACCESS_TOKEN_KEY => $accessToken->getAccessToken(),
            self::REFRESH_TOKEN_KEY => $accessToken->getRefreshToken(),
            self::REALM_ID_KEY => $accessToken->getRealmID(),
        ])->save();
    }

    public static function forgetDataServiceInfo(): void
    {
        Setting::forget(self::ACCESS_TOKEN_KEY);
        Setting::forget(self::REFRESH_TOKEN_KEY);
        Setting::forget(self::REALM_ID_KEY);
        Setting::save();
    }
}
