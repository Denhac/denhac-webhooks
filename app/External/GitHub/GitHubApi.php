<?php

namespace App\External\GitHub;

use Illuminate\Support\Facades\Http;

class GitHubApi
{
    private TokenManager $tokenManager;

    private ?string $accessToken = null;

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

    public function organizations($name): OrganizationApi
    {
        return new OrganizationApi($name, $this->getAccessToken());
    }

    /**
     * Convenience function since we most often need to access the denhac organization.
     *
     * @return OrganizationApi
     */
    public function denhac(): OrganizationApi
    {
        return $this->organizations("denhac");
    }

    public function userLookup($username)
    {
        // TODO switch this to be under a users module
        $accessToken = $this->getAccessToken();
        $response = Http::withHeaders([
            'Authorization' => "Bearer $accessToken",
        ])->get("https://api.github.com/users/$username");

        return $response->json();
    }

    public function emailLookup($email)
    {
        // TODO switch this to be under search module
        $accessToken = $this->getAccessToken();
        $response = Http::withHeaders([
            'Authorization' => "Bearer $accessToken",
        ])->get("https://api.github.com/search/users?q=$email");

        return $response->json();
    }
}
