<?php

namespace App\GitHub;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class TeamApi
{
    private string $accessToken;
    private Client $client;
    private string $teamUrl;

    /**
     * TeamApi constructor.
     * @param $name
     * @param string $accessToken
     */
    public function __construct($name, string $accessToken)
    {
        $this->accessToken = $accessToken;

        $this->teamUrl = "https://api.github.com/orgs/denhac/teams/$name";
        $this->client = new Client();
    }

    public function list()
    {
        $response = $this->client->get("{$this->teamUrl}/members", [
            RequestOptions::HEADERS => [
                'Authorization' => "Bearer {$this->accessToken}",
            ],
        ]);

        $json = json_decode($response->getBody(), true);

        return collect($json)
            ->map(function ($member) {
                return $member['login'];
            });
    }

    public function add($username)
    {
        $membershipUrl = "{$this->teamUrl}/memberships/$username";

        $this->client->put($membershipUrl, [
            RequestOptions::HEADERS => [
                'Authorization' => "Bearer {$this->accessToken}",
            ],
        ]);
    }

    public function remove($username)
    {
        $membershipUrl = "{$this->teamUrl}/memberships/$username";

        $this->client->delete($membershipUrl, [
            RequestOptions::HEADERS => [
                'Authorization' => "Bearer {$this->accessToken}",
            ],
        ]);
    }
}
