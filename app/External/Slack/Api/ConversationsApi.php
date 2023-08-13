<?php

namespace App\External\Slack\Api;


use App\External\Slack\SlackApi;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ConversationsApi
{
    use SlackApiTrait;

    private SlackClients $clients;

    public function __construct(SlackClients $clients)
    {
        $this->clients = $clients;
    }

    /**
     * Note: Helper method, not official slack API
     *
     * @param $wantedChannels
     * @return Collection
     */
    public function toSlackIds($wantedChannels): Collection
    {
        $wantedChannels = Arr::wrap($wantedChannels);

        return $this->list()
            ->filter(fn($ch) => (
                in_array($ch['id'], $wantedChannels) || in_array($ch['name'], $wantedChannels)
            ))
            ->map(fn($channel) => $channel['id'])
            ->values();
    }

    public function create($name, $private = false)
    {
        $response = $this->clients->managementApiClient
            ->post('https://denhac.slack.com/api/conversations.create', [
                RequestOptions::FORM_PARAMS => [
                    'name' => $name,
                    'is_private' => $private
                ],
            ]);

        return json_decode($response->getBody(), true);
    }

    public function list(...$types): Collection
    {
        if(empty($types)) {
            $types = [
                SlackApi::PUBLIC_CHANNEL,
                SlackApi::PRIVATE_CHANNEL,
            ];
        }

        $typeString = implode(',', $types);

        return $this->paginate('channels', function ($cursor) use ($typeString) {
            return $this->clients->managementApiClient
                ->get('https://denhac.slack.com/api/conversations.list', [
                    RequestOptions::QUERY => [
                        'types' => $typeString,
                        'cursor' => $cursor,
                    ],
                ]);
        });
    }

    public function members($channelId): Collection
    {
        return $this->paginate('members', function ($cursor) use ($channelId) {
            return $this->clients->managementApiClient
                ->get('https://denhac.slack.com/api/conversations.members', [
                    RequestOptions::QUERY => [
                        'channel' => $channelId,
                        'cursor' => $cursor,
                    ],
                ]);
        });
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
