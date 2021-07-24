<?php

namespace App\Slack\api;


use GuzzleHttp\RequestOptions;

class ConversationsApi
{
    use SlackApiTrait;

    private SlackClients $clients;

    public function __construct(SlackClients $clients)
    {
        $this->clients = $clients;
    }

    public function join($channelId)
    {
        $response = $this->clients->managementApiClient
            ->post('https://denhac.slack.com/api/conversations.join', [
                RequestOptions::FORM_PARAMS => [
                    'channel' => $channelId,
                ],
            ]);

        return json_decode($response->getBody(), true);
    }

    public function invite(string $userId, $channelId)
    {
        $response = $this->clients->managementApiClient
            ->post('https://denhac.slack.com/api/conversations.invite', [
                RequestOptions::FORM_PARAMS => [
                    'channel' => $channelId,
                    'users' => $userId,
                ],
            ]);

        return json_decode($response->getBody(), true);
    }

    public function kick(string $userId, $channelId)
    {
        $response = $this->clients->managementApiClient
            ->post('https://denhac.slack.com/api/conversations.kick', [
                RequestOptions::FORM_PARAMS => [
                    'channel' => $channelId,
                    'user' => $userId,
                ],
            ]);

        return json_decode($response->getBody(), true);
    }
}
