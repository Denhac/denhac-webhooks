<?php

namespace App\Slack\api;


use GuzzleHttp\RequestOptions;
use Illuminate\Support\Collection;

class UsersApi
{
    use SlackApiTrait;

    private SlackClients $clients;

    public function __construct(SlackClients $clients)
    {
        $this->clients = $clients;
    }

    public function list(): Collection
    {
        return $this->paginate('members', function ($cursor) {
            return $this->clients->managementApiClient
                ->get(
                    'https://denhac.slack.com/api/users.list', [
                    RequestOptions::QUERY => [
                        'cursor' => $cursor,
                    ],
                ]);
        });
    }
}
