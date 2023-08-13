<?php

namespace App\External\Slack\Api;


class TeamApi
{
    use SlackApiTrait;

    private SlackClients $clients;

    public function __construct(SlackClients $clients)
    {
        $this->clients = $clients;
    }

    public function accessLogs()
    {
        $response = $this->clients->adminClient
            ->get('https://denhac.slack.com/api/team.accessLogs');

        return json_decode($response->getBody(), true)['logins'];
    }
}
