<?php

namespace App\Slack\api;


use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

/**
 * @property Client managementApiClient
 * @property Client spaceBotApiClient
 * @property Client adminClient
 */
class SlackClients
{
    private const ADMIN_TOKEN_CACHE_KEY = 'slack.admin.token';
    protected array $lazyBindings = [];

    public function __get(string $name)
    {
        $client = null;
        if(array_key_exists($name, $this->lazyBindings)) {
            $client = $this->lazyBindings[$name];
        } else if($name == 'managementApiClient') {
            $client = $this->clientFromToken(config('denhac.slack.management_api_token'));
        } else if($name == 'spaceBotApiClient') {
            $client = $this->clientFromToken(config('denhac.slack.spacebot_api_token'));
        } else if($name == 'adminClient') {
            // TODO Try to verify how long this key lasts and when we need to refresh it
            $client = $this->clientFromToken(setting(self::ADMIN_TOKEN_CACHE_KEY));
        }

        if(! is_null($client)) {
            $this->lazyBindings[$name] = $client;
        }

        return $client;
    }

    private function clientFromToken($token): Client {
        return new Client([
            RequestOptions::HEADERS => [
                'Authorization' => "Bearer $token",
            ],
        ]);
    }
}
