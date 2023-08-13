<?php

namespace App\External\Slack\Api;


use App\External\ApiProgress;
use App\External\Slack\UnexpectedResponseException;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Collection;
use JetBrains\PhpStorm\Pure;

/**
 * @property UsersAdminApi admin
 * @property UsersProfileApi profile
 */
class UsersApi
{
    use SlackApiTrait;

    private SlackClients $clients;

    public function __construct(SlackClients $clients)
    {
        $this->clients = $clients;
    }

    #[Pure] public function __get(string $name)
    {
        if ($name == 'admin') {
            return new UsersAdminApi($this->clients);
        } else if ($name == 'profile') {
            return new UsersProfileApi($this->clients);
        }

        return null;
    }

    public function list(ApiProgress $progress = null): Collection
    {
        return $this->paginate('members', function ($cursor) {
            return $this->clients->managementApiClient
                ->get(
                    'https://denhac.slack.com/api/users.list', [
                    RequestOptions::QUERY => [
                        'cursor' => $cursor,
                        'limit' => 200,
                    ],
                ]);
        }, $progress);
    }

    public function lookupByEmail($email)
    {
        // TODO Handle user not found/ok is false
        $response = $this->clients->managementApiClient->get('https://denhac.slack.com/api/users.lookupByEmail', [
            RequestOptions::QUERY => [
                'email' => $email,
            ],
        ]);

        $data = json_decode($response->getBody(), true);

        if ($data['ok']) {
            return $data['user'];
        }

        if ($data['error'] == 'users_not_found') {
            report(new UnexpectedResponseException("Some error: {$response->getBody()}"));

            return null;
        }

        if (!array_key_exists('user', $data)) {
            report(new UnexpectedResponseException("No User key exists: {$response->getBody()}"));

            return null;
        }

        return null;
    }
}
