<?php

namespace App\Github;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class TokenManager
{
    private $signingKey;
    private $appId;
    private $installationId;
    /**
     * @var Client
     */
    private $client;

    public function __construct()
    {
        $this->signingKey = file_get_contents(config('denhac.github.key_path'));
        $this->appId = config('denhac.github.app_id');
        $this->installationId = config('denhac.github.installation_id');

        $this->client = new Client();
    }

    private function base64url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    // TODO Refactor this to work for specific logins / organizations to get installation id automatically
    public function getInstallationAccessToken()
    {
        $appAccessToken = $this->getAppAccessToken();

        $installationUrl = "https://api.github.com/app/installations/{$this->installationId}/access_tokens";

        $response = $this->client->post($installationUrl, [
            RequestOptions::HEADERS => [
                'Accept' => 'application/vnd.github.machine-man-preview+json',
                'Authorization' => "Bearer $appAccessToken",
            ],
        ]);

        return json_decode($response->getBody(), true)['token'];
    }

    private function getAppAccessToken()
    {
        $jwtHeader = $this->base64url_encode(json_encode([
            'alg' => 'RS256',
        ]));

        $jwtClaims = $this->base64url_encode(json_encode([
            'iat' => time(),
            'exp' => time() + (10 * 60), // 10 minute max
            'iss' => $this->appId,
        ]));

        $privateKeyId = openssl_pkey_get_private($this->signingKey);
        $signature = '';
        openssl_sign($jwtHeader.'.'.$jwtClaims, $signature, $privateKeyId, 'sha256WithRSAEncryption');

        $jwtSignature = $this->base64url_encode($signature);

        return $jwtHeader.'.'.$jwtClaims.'.'.$jwtSignature;
    }
}
