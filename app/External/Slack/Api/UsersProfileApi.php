<?php

namespace App\External\Slack\Api;

use App\External\Slack\SlackRateLimit;
use GuzzleHttp\RequestOptions;

class UsersProfileApi
{
    use SlackApiTrait;

    private SlackClients $clients;

    public function __construct(SlackClients $clients)
    {
        $this->clients = $clients;
    }

    public function set($user_id, $profile)
    {
        SlackRateLimit::users_profile_set()->hit();

        return $this->clients->adminClient
            ->post('https://denhac.slack.com/api/users.profile.set', [
                RequestOptions::JSON => [
                    'user' => $user_id,
                    'profile' => $profile,
                ],
            ]);
    }

    public function get($user_id)
    {
        SlackRateLimit::users_profile_get()->hit();

        $response = $this->clients->adminClient
            ->get('https://denhac.slack.com/api/users.profile.get', [
                RequestOptions::QUERY => [
                    'user' => $user_id,
                ],
            ]);

        return json_decode($response->getBody(), true);
    }
}
