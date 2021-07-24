<?php

namespace App\Slack\api;


use GuzzleHttp\RequestOptions;
use Illuminate\Support\Collection;

class UsergroupsApi
{
    use SlackApiTrait;

    private SlackClients $clients;

    public function __construct(SlackClients $clients)
    {
        $this->clients = $clients;
    }

    public function list(): Collection
    {
        // TODO Make this handle errors/pagination
        return collect(json_decode($this->clients->managementApiClient
            ->get('https://denhac.slack.com/api/usergroups.list', [
                RequestOptions::QUERY => [
                    'include_users' => true,
                ],
            ])
            ->getBody(), true)['usergroups']);
    }
}
