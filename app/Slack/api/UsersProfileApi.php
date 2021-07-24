<?php

namespace App\Slack\api;


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
}
