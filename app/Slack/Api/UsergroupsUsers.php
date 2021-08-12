<?php

namespace App\Slack\Api;


use GuzzleHttp\RequestOptions;
use Illuminate\Support\Collection;

class UsergroupsUsers
{
    use SlackApiTrait;

    private SlackClients $clients;

    public function __construct(SlackClients $clients)
    {
        $this->clients = $clients;
    }

    public function update($usergroupId, Collection $users)
    {
        $response = $this->clients->adminClient
            ->post('https://denhac.slack.com/api/usergroups.users.update', [
                RequestOptions::FORM_PARAMS => [
                    'usergroup' => $usergroupId,
                    'users' => $users->implode(','),
                ],
            ]);

        return json_decode($response->getBody(), true)['ok'];
    }
}
