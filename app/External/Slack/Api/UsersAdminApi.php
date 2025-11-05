<?php

namespace App\External\Slack\Api;

use GuzzleHttp\RequestOptions;

class UsersAdminApi
{
    use SlackApiTrait;

    private SlackClients $clients;

    public function __construct(SlackClients $clients)
    {
        $this->clients = $clients;
    }

    public function setRegular($slack_id)
    {
        $response = $this->clients->adminClient
            ->post('https://denhac.slack.com/api/users.admin.setRegular', [
                RequestOptions::FORM_PARAMS => [
                    'user' => $slack_id,
                ],
            ]);

        return json_decode($response->getBody(), true)['ok'];
    }

    public function setUltraRestricted($slack_id, $channel_id)
    {
        $response = $this->clients->adminClient
            ->post('https://denhac.slack.com/api/users.admin.setUltraRestricted', [
                RequestOptions::FORM_PARAMS => [
                    'user' => $slack_id,
                    'channel' => $channel_id,
                ],
            ]);

        return json_decode($response->getBody(), true)['ok'];
    }

    public function setInactive($slack_id)
    {
        $response = $this->clients->adminClient
            ->post('https://denhac.slack.com/api/users.admin.setInactive', [
                RequestOptions::FORM_PARAMS => [
                    'user' => $slack_id,
                ],
            ]);

        return json_decode($response->getBody(), true)['ok'];
    }
}
