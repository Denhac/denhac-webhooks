<?php

namespace App\GitHub;

use Illuminate\Support\Facades\Http;

class GitHubApi
{
    /**
     * @var TokenManager
     */
    private TokenManager $tokenManager;
    private string|null $accessToken = null;

    public function __construct(TokenManager $tokenManager)
    {
        $this->tokenManager = $tokenManager;
    }

    private function getAccessToken(): string
    {
        if (is_null($this->accessToken)) {
            $this->accessToken = $this->tokenManager->getInstallationAccessToken();
        }

        return $this->accessToken;
    }

    public function team($name)
    {
        // TODO switch this to be under orgs even though we'll almost always be using denhac
        return new TeamApi($name, $this->getAccessToken());
    }

    public function userLookup($username)
    {
        // TODO switch this to be under a users module
        $accessToken = $this->getAccessToken();
        $response = Http::withHeaders([
            'Authorization' => "Bearer $accessToken"
        ])->get("https://api.github.com/users/$username");
        return $response->json();
    }

    public function emailLookup($email)
    {
        // TODO switch this to be under search module
        $accessToken = $this->getAccessToken();
        $response = Http::withHeaders([
            'Authorization' => "Bearer $accessToken"
        ])->get("https://api.github.com/search/users?q=$email");
        return $response->json();
    }
}
