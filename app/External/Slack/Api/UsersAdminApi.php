<?php

namespace App\External\Slack\Api;

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
     * @throws GuzzleException
     */
    public function inviteBulk($emails, $channels)
    {
        $emails = Arr::wrap($emails);
        if (! Arr::isAssoc($emails)) {
            $emails = array_fill_keys($emails, 'regular');
        }

        if (is_array($channels)) {
            $channels = implode(',', $channels);
        }

        $invites = collect($emails)->map(function ($value, $key) {
            return [
                'email' => $key,
                'type' => $value,
                'mode' => 'manual',
            ];
        });

        $restricted = ! $invites->where('type', 'restricted')->isEmpty();
        $ultraRestricted = ! $invites->where('type', 'ultra_restricted')->isEmpty();

        $response = $this->clients->adminClient
            ->post('https://denhac.slack.com/api/users.admin.inviteBulk', [
                RequestOptions::MULTIPART => [
                    $this->_multipart('invites', json_encode($invites->all())),
                    $this->_multipart('source', 'invite_modal'),
                    $this->_multipart('campaign', 'team_site_admin'),
                    $this->_multipart('mode', 'manual'),
                    $this->_multipart('restricted', $restricted),
                    $this->_multipart('ultra_restricted', $ultraRestricted),
                    $this->_multipart('email_password_policy_enabled', false),
                    $this->_multipart('channels', $channels),
                    $this->_multipart('_x_reason', 'invite_bulk'),
                    $this->_multipart('_x_mode', 'online'),
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
