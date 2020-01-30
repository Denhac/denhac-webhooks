<?php

namespace App\Google;


use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;

class GoogleApi
{
    // I'm thinking this could be a class interface where I say $googleApi->groups and use it that way,
    // or $googleApi->group("members@denhac.org")->add/remove/etc.
    // Let's start by breaking the service key and private key path out into config variables.
    // Next, I'm thinking about a token issuer type class that can be used to get tokens with the correct scope automatically.
    // That class can be passed into whatever class is returned by the group method or whatever.


    /**
     * @var TokenManager
     */
    private $tokenManager;

    public function __construct(TokenManager $tokenManager)
    {
        $this->tokenManager = $tokenManager;
    }

    public function group($name)
    {
        return new GroupApi($this->tokenManager, $name);
    }

    public function groupsForDomain(string $domain)
    {
        $groupScope = "https://www.googleapis.com/auth/admin.directory.group";

        $accessToken = $this->tokenManager->getAccessToken($groupScope);

        $client = new Client();

        $response = $client->get("https://www.googleapis.com/admin/directory/v1/groups", [
            RequestOptions::HEADERS => [
                'Authorization' => "Bearer $accessToken",
            ],
            RequestOptions::QUERY => [
                "domain" => $domain,
            ],
        ]);

        $json = json_decode($response->getBody(), true);

        if (Arr::has($json, "groups")) {
            return collect($json["groups"])
                ->map(function ($group) {
                    return $group["email"];
                });
        }
    }

    public function groupsForMember(string $email)
    {
        $groupScope = "https://www.googleapis.com/auth/admin.directory.group";

        $accessToken = $this->tokenManager->getAccessToken($groupScope);

        $client = new Client();

        $response = $client->get("https://www.googleapis.com/admin/directory/v1/groups", [
            RequestOptions::HEADERS => [
                'Authorization' => "Bearer $accessToken",
            ],
            RequestOptions::QUERY => [
                "userKey" => $email,
            ],
        ]);

        $json = json_decode($response->getBody(), true);

        if (Arr::has($json, "groups")) {
            return collect($json["groups"])
                ->map(function ($group) {
                    return $group["email"];
                });
        }
    }
}
