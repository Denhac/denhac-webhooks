<?php

namespace App\Slack\api;


use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;

class UsersAdminApi
{
    use SlackApiTrait;

    private SlackClients $clients;

    public function __construct(SlackClients $clients)
    {
        $this->clients = $clients;
    }

    /**
     * This method invites some number of users to the slack workspace. If $emails is just a string, it will be treated
     * as just inviting that user as a regular member. If it's an array of emails, all of those emails will be added as
     * regular members. If a key/value array is supplied, the key will be treated as an email and the value will be
     * what membership level to add them. e.g., regular, restricted, or ultra_restricted.
     *
     * Channels should either be a comma separated list of channel ids, or an array of channel names.
     *
     * @param $emails
     * @param $channels
     * @return array
     * @throws GuzzleException
     */
    public function inviteBulk($emails, $channels): array
    {
        $emails = Arr::wrap($emails);
        if (!Arr::isAssoc($emails)) {
            $emails = array_fill_keys($emails, 'regular');
        }

        if (is_array($channels)) {
            $channels = implode(',', $channels);
        }

        $invites = collect($emails)->map(function ($value, $key) {
            return [
                'email' => $key,
                'type' => $value,
                'source' => 'invite_modal',
                'mode' => 'manual',
            ];
        });

        $response = $this->clients->adminClient
            ->post('https://denhac.slack.com/api/users.admin.inviteBulk', [
                RequestOptions::FORM_PARAMS => [
                    'invites' => json_encode($invites->all()),
                    'channels' => $channels,
                ],
            ]);

        return json_decode($response->getBody(), true);
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
}
