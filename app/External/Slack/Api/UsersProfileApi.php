<?php

namespace App\External\Slack\Api;

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
        $response = $this->clients->adminClient
            ->get('https://denhac.slack.com/api/users.profile.get', [
                RequestOptions::QUERY => [
                    'user' => $user_id,
                ],
            ]);

        return json_decode($response->getBody(), true);
    }
}
