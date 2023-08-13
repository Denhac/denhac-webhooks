<?php

namespace App\External\Slack\Api;


use GuzzleHttp\RequestOptions;
use Illuminate\Support\Collection;
use JetBrains\PhpStorm\Pure;

/**
 * @property UsergroupsUsers users
 */
class UsergroupsApi
{
    use SlackApiTrait;

    private SlackClients $clients;

    public function __construct(SlackClients $clients)
    {
        $this->clients = $clients;
    }

    #[Pure] public function __get(string $name)
    {
        if ($name == 'users') {
            return new UsergroupsUsers($this->clients);
        }

        return null;
    }

    /**
     * Note: Helper method, not official slack API
     * @param $handle
     * @return array
     */
    public function byName($handle) {
        return $this->list()
            ->firstWhere('handle', $handle);
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
