<?php

namespace App\Slack\Api;


use GuzzleHttp\RequestOptions;

class ViewsApi
{
    use SlackApiTrait;

    private SlackClients $clients;

    public function __construct(SlackClients $clients)
    {
        $this->clients = $clients;
    }

    public function open($trigger_id, $view)
    {
        return $this->clients->spaceBotApiClient
            ->post('https://denhac.slack.com/api/views.open', [
                RequestOptions::JSON => [
                    'trigger_id' => $trigger_id,
                    'view' => json_encode($view),
                ],
            ]);
    }

    public function publish($user_id, $view)
    {
        $this->clients->spaceBotApiClient
            ->post('https://denhac.slack.com/api/views.publish', [
                RequestOptions::JSON => [
                    'user_id' => $user_id,
                    'view' => json_encode($view),
                ],
            ]);
    }

    public function update($view_id, $view, $hash = null)
    {
        $data = [
            'view_id' => $view_id,
            'view' => json_encode($view),
        ];

        if (!is_null($hash)) {
            $data['hash'] = $hash;
        }

        $this->clients->spaceBotApiClient
            ->post('https://denhac.slack.com/api/views.update', [
                RequestOptions::JSON => $data,
            ]);
    }
}
