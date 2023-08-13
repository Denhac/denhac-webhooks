<?php

namespace App\External\Google;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class TokenManager
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private bool $propertiesBound = false;
    private string $apiKey;
    private string $authAs;
    private string $serviceAccount;
    private Client $client;

    private function lateBindProperties()
    {
        if($this->propertiesBound) {
            return;
        }

        $this->apiKey = file_get_contents(config('denhac.google.key_path'));
        $this->serviceAccount = config('denhac.google.service_account');
        $this->authAs = config('denhac.google.auth_as');

        $this->client = new Client();
        $this->propertiesBound = true;
    }

    public function getAccessToken($scopes)
    {
        if (is_array($scopes)) {
            $scopes = implode(',', $scopes);
        }

        $jwtToken = $this->getJwtToken($scopes);

        return $this->refreshToken($jwtToken);
    }

    private function base64url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function getJwtToken(string $scopes)
    {
        $this->lateBindProperties();

        $jwtHeader = $this->base64url_encode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ]));

        $jwtClaims = $this->base64url_encode(json_encode([
            'iss' => $this->serviceAccount,
            'scope' => $scopes,
            'aud' => self::TOKEN_URL,
            'sub' => $this->authAs,
            'exp' => time() + (10 * 60),
            'iat' => time(),
        ]));

        $privateKeyId = openssl_pkey_get_private($this->apiKey);
        $signature = '';
        openssl_sign($jwtHeader.'.'.$jwtClaims, $signature, $privateKeyId, 'sha256WithRSAEncryption');

        $jwtSignature = $this->base64url_encode($signature);

        return $jwtHeader.'.'.$jwtClaims.'.'.$jwtSignature;
    }

    private function refreshToken($jwtToken)
    {
        $this->lateBindProperties();

        $response = $this->client->post(self::TOKEN_URL, [
            RequestOptions::HEADERS => [
                'Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8',
            ],
            RequestOptions::BODY => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwtToken,
            ]),
        ]);

        // TODO Check for error
        return json_decode($response->getBody(), true)['access_token'];
    }
}
