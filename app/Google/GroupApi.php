<?php

namespace App\Google;


use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;

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

        /** @var ResponseInterface $response */
        $response = $this->client->post($this->membersUrl, [
            RequestOptions::HEADERS => [
                'Authorization' => "Bearer $accessToken"
            ],
            RequestOptions::JSON => [
                "email" => $email,
                "role" => "MEMBER"
            ],
            RequestOptions::HTTP_ERRORS => false,
        ]);

        if ($response->getStatusCode() == Response::HTTP_CONFLICT && $this->errorsHasDuplicate($response)) {
            // Not an issue, they've already been added
        } else if ($response->getStatusCode() != Response::HTTP_OK) {
            throw new \Exception("Google api add failed: " . $response->getBody());
        }

        // TODO Handle conflict/other errors
    }

    public function remove(string $email)
    {
        $accessToken = $this->tokenManager->getAccessToken(self::GROUP_SCOPE);

        /** @var ResponseInterface $response */
        $response = $this->client->delete("{$this->membersUrl}/$email", [
            RequestOptions::HEADERS => [
                'Authorization' => "Bearer $accessToken"
            ],
        ]);

        // TODO Handle conflict/other errors
    }

    /**
     * @param $response ResponseInterface
     * @return bool
     */
    private function errorsHasDuplicate($response)
    {
        $json = json_decode($response->getBody(), true);

        if (!Arr::has($json, "error")) {
            return false;
        }
        $error = $json["error"];

        if (!Arr::has($error, "code")) {
            return false;
        }

        $code = $error["code"];

        if($code != 409) {
            return false;
        }

        if (!Arr::has($error, "errors")) {
            return false;
        }

        $errors = collect($error["errors"]);

        return $errors
            ->filter(function ($e) {
                return Arr::has($e, "reason") && $e["reason"] === "duplicate";
            })
            ->isNotEmpty();
    }
}
