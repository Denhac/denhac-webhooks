<?php

namespace App\Google;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;

class TokenManager
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private $apiKey;
    private $authAs;
    private $serviceAccount;
    /**
     * @var Client
     */
    private $client;

    public function __construct($apiKey, $serviceAccount, $authAs)
    {
        $this->apiKey = $apiKey;
        $this->serviceAccount = $serviceAccount;
        $this->authAs = $authAs;

        $this->client = new Client();
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
