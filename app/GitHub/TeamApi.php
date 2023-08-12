<?php

namespace App\GitHub;

use App\External\ApiProgress;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class TeamApi
{
    use GitHubApiTrait;

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

    public function list(ApiProgress $progress = null)
    {
        return $this->paginate("{$this->teamUrl}/members", function ($url) {
            return $this->client->get($url, [
                RequestOptions::HEADERS => [
                    'Authorization' => "Bearer {$this->accessToken}",
                    'per_page' => 100,
                ],
            ]);
        }, $progress);
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
