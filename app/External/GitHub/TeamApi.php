<?php

namespace App\External\GitHub;

use App\External\ApiProgress;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Collection;

class TeamApi
{
    use GitHubApiTrait;

    private string $accessToken;

    private Client $client;

    private string $teamUrl;

    /**
     * TeamApi constructor.
     */
    public function __construct($orgName, $name, string $accessToken)
    {
        $this->accessToken = $accessToken;

        $this->teamUrl = "https://api.github.com/orgs/$orgName/teams/$name";
        $this->client = new Client;
    }

    public function list(?ApiProgress $progress = null): Collection
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

    public function pending(?ApiProgress $progress = null): Collection
    {
        return $this->paginate("{$this->teamUrl}/invitations", function ($url) {
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

        $response = $this->client->put($membershipUrl, [
            RequestOptions::HEADERS => [
                'Authorization' => "Bearer {$this->accessToken}",
            ],
        ]);

        error_log($response->getStatusCode());
        error_log($response->getBody()->getContents());
    }

    public function remove($username): void
    {
        $membershipUrl = "{$this->teamUrl}/memberships/$username";

        $this->client->delete($membershipUrl, [
            RequestOptions::HEADERS => [
                'Authorization' => "Bearer {$this->accessToken}",
            ],
        ]);
    }
}
