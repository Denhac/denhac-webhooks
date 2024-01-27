<?php

namespace App\External\GitHub;

use App\External\ApiProgress;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Collection;

class OrganizationApi
{
    use GitHubApiTrait;

    private string $accessToken;

    private Client $client;

    private string $organizationName;
    private string $organizationUrl;

    /**
     * TeamApi constructor.
     */
    public function __construct($name, string $accessToken)
    {
        $this->organizationName = $name;
        $this->accessToken = $accessToken;

        $this->organizationUrl = "https://api.github.com/orgs/$name";
        $this->client = new Client();
    }

    public function team($name): TeamApi
    {
        return new TeamApi($this->organizationName, $name, $this->accessToken);
    }

    public function listMembers(ApiProgress $progress = null): Collection
    {
        return $this->paginate("{$this->organizationUrl}/members", function ($url) {
            return $this->client->get($url, [
                RequestOptions::HEADERS => [
                    'Authorization' => "Bearer {$this->accessToken}",
                    'per_page' => 100,
                ],
            ]);
        }, $progress);
    }

    public function pendingInvitations(ApiProgress $progress = null): Collection
    {
        return $this->paginate("{$this->organizationUrl}/invitations", function ($url) {
            return $this->client->get($url, [
                RequestOptions::HEADERS => [
                    'Authorization' => "Bearer {$this->accessToken}",
                    'per_page' => 100,
                ],
            ]);
        }, $progress);
    }

    public function failedInvitations(ApiProgress $progress = null): Collection
    {
        return $this->paginate("{$this->organizationUrl}/failed_invitations", function ($url) {
            return $this->client->get($url, [
                RequestOptions::HEADERS => [
                    'Authorization' => "Bearer {$this->accessToken}",
                    'per_page' => 100,
                ],
            ]);
        }, $progress);
    }
}
