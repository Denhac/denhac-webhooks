<?php

namespace App\Google;


use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class GroupApi
{
    private const GROUP_SCOPE = "https://www.googleapis.com/auth/admin.directory.group";
    private $group;
    /**
     * @var TokenManager
     */
    private $tokenManager;

    private $membersUrl;
    /**
     * @var Client
     */
    private $client;

    public function __construct(TokenManager $tokenManager, string $group)
    {
        $this->tokenManager = $tokenManager;
        $this->group = $group;

        $encodedGroupName = urlencode($group);
        $this->membersUrl = "https://www.googleapis.com/admin/directory/v1/groups/{$encodedGroupName}/members";
        $this->client = new Client();
    }

    public function add(string $email)
    {
        $accessToken = $this->tokenManager->getAccessToken(self::GROUP_SCOPE);
        $response = $this->client->post($this->membersUrl, [
            RequestOptions::HEADERS => [
                'Authorization' => "Bearer {$accessToken}"
            ],
            RequestOptions::JSON => [
                "email" => $email,
                "role" => "MEMBER"
            ]
        ]);

        // TODO Handle conflict
    }
}
