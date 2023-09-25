<?php

namespace App\External\QuickBooks;

use anlutro\LaravelSettings\Facades\Setting;
use Illuminate\Support\Facades\Crypt;
use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2AccessToken;
use QuickBooksOnline\API\DataService\DataService;

class QuickBooksAuthSettings
{
    protected const ACCESS_TOKEN_KEY = 'quickbooks.auth.accessToken';

    protected const REFRESH_TOKEN_KEY = 'quickbooks.auth.refreshToken';

    protected const REALM_ID_KEY = 'quickbooks.auth.realmId';

    public static function hasKnownAuth(): bool
    {
        return ! is_null(setting(self::ACCESS_TOKEN_KEY)) ||
            ! is_null(setting(self::REFRESH_TOKEN_KEY)) ||
            ! is_null(setting(self::REALM_ID_KEY));
    }

    public static function getRealmId(): ?string
    {
        if (! self::hasKnownAuth()) {
            return null;
        }

        return setting(self::REALM_ID_KEY);
    }

    public static function getDataServiceParameters(): array
    {
        $dataServiceParameters = [
            'auth_mode' => 'oauth2',
            'ClientID' => config('denhac.quickbooks.client_id'),
            'ClientSecret' => config('denhac.quickbooks.client_secret'),
            'RedirectURI' => config('denhac.quickbooks.redirect'),
            'scope' => 'com.intuit.quickbooks.accounting',
            'baseUrl' => config('denhac.quickbooks.base_url'),
        ];

        $accessToken = setting(self::ACCESS_TOKEN_KEY);
        if (! is_null($accessToken)) {
            $dataServiceParameters['accessTokenKey'] = $accessToken;
        }

        $refreshToken = setting(self::REFRESH_TOKEN_KEY);
        if (! is_null($refreshToken)) {
            $dataServiceParameters['refreshTokenKey'] = Crypt::decryptString($refreshToken);
        }

        $realmId = setting(self::REALM_ID_KEY);
        if (! is_null($realmId)) {
            $dataServiceParameters['QBORealmID'] = $realmId;
        }

        return $dataServiceParameters;
    }

    public static function saveDataServiceInfo(OAuth2AccessToken $accessToken): void
    {
        setting([
            self::ACCESS_TOKEN_KEY => $accessToken->getAccessToken(),
            self::REFRESH_TOKEN_KEY => Crypt::encryptString($accessToken->getRefreshToken()),
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
