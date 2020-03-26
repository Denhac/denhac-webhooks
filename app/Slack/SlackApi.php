<?php

namespace App\Slack;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SlackApi
{
    private const ADMIN_TOKEN_CACHE_KEY = 'slack.admin.token';
    /**
     * @var Client
     */
    private $apiClient;
    /**
     * @var Client
     */
    private $adminClient;

    public function __construct($apiToken, $email, $password)
    {
        $this->apiClient = new Client([
            RequestOptions::HEADERS => [
                'Authorization' => "Bearer $apiToken",
            ],
        ]);

        $this->adminClient = $this->getAdminClient($email, $password);
    }

    private function getAdminClient($email, $password)
    {
        $token = Cache::get(self::ADMIN_TOKEN_CACHE_KEY);

        if (is_null($token) || ! $this->isValidToken($token)) {
            $token = $this->makeAdminToken($email, $password);
            Cache::forever(self::ADMIN_TOKEN_CACHE_KEY, $token);
        }

        return new Client([
            RequestOptions::HEADERS => [
                'Authorization' => "Bearer $token",
            ],
        ]);
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
     * @return mixed
     */
    public function users_admin_inviteBulk($emails, $channels)
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
                'source' => 'invite_modal',
                'mode' => 'manual',
            ];
        });

        $response = $this->adminClient
            ->post('https://denhac.slack.com/api/users.admin.inviteBulk', [
                RequestOptions::FORM_PARAMS => [
                    'invites' => json_encode($invites->all()),
                    'channels' => $channels,
                ],
            ]);

        return json_decode($response->getBody(), true);
    }

    public function users_admin_setRegular($slack_id)
    {
        $response = $this->adminClient
            ->post('https://denhac.slack.com/api/users.admin.setRegular', [
                RequestOptions::FORM_PARAMS => [
                    'user' => $slack_id,
                ],
            ]);

        return json_decode($response->getBody(), true)['ok'];
    }

    public function users_admin_setUltraRestricted($slack_id, $channel_id)
    {
        $response = $this->adminClient
            ->post('https://denhac.slack.com/api/users.admin.setUltraRestricted', [
                RequestOptions::FORM_PARAMS => [
                    'user' => $slack_id,
                    'channel' => $channel_id,
                ],
            ]);

        return json_decode($response->getBody(), true)['ok'];
    }

    public function users_list()
    {
        // TODO Make this handle errors/pagination
        return collect(json_decode($this->apiClient
            ->get('https://denhac.slack.com/api/users.list')
            ->getBody(), true)['members']);
    }

    public function users_lookupByEmail($email)
    {
        // TODO Handle user not found/ok is false
        $response = $this->apiClient->get('https://denhac.slack.com/api/users.lookupByEmail', [
            RequestOptions::QUERY => [
                'email' => $email,
            ],
        ]);

        return json_decode($response->getBody(), true)['user'];
    }

    public function channels_list()
    {
        // TODO Make this handle errors/pagination
        return collect(json_decode($this->apiClient
            ->get('https://denhac.slack.com/api/conversations.list', [
                RequestOptions::QUERY => [
                    'types' => "public_channel,private_channel",
                ],
            ])
            ->getBody(), true)["channels"]);
    }

    private function isValidToken($token)
    {
        // TODO Handle errors/exceptions at some point if needed
        // I want it to throw an exception until I know all the ways it can fail.
        $response = (new Client())->get('https://denhac.slack.com/api/api.test', [
            RequestOptions::HEADERS => [
                'Authorization' => "Bearer $token",
            ],
//            RequestOptions::HTTP_ERRORS => false,
        ]);

        $json = json_decode($response->getBody(), true);

        return $json['ok'];
    }

    private function makeAdminToken($email, $password)
    {
        $client = new \Goutte\Client();
        $crawler = $client->request('GET', 'https://denhac.slack.com/?no_sso=1');
        $form = $crawler->selectButton('Sign in')->form();
        $client->submit($form, ['email' => $email, 'password' => $password]);
        // TODO Handle the .alert_error class

        $html = $client->request('GET', 'https://denhac.slack.com/admin')->html();

        $matches = [];
        $regex = '/"api_token"\s*:\s*"(xoxs-[^"]+)/';
        preg_match($regex, $html, $matches);

        return $matches[1];
    }

    /**
     * @param $wantedChannels
     * @return array
     */
    public function channelIdsByName($wantedChannels)
    {
        $wantedChannels = Arr::wrap($wantedChannels);
        $channels = $this->channels_list();

        return $channels
            ->whereIn('name', $wantedChannels)
            ->map(function ($channel) {
                return $channel['id'];
            })
            ->all();
    }

    public function conversations_invite(string $userId, $channelId)
    {
        $response = $this->apiClient
            ->post('https://denhac.slack.com/api/conversations.invite', [
                RequestOptions::FORM_PARAMS => [
                    'channel' => $channelId,
                    'users' => $userId,
                ],
            ]);

        return json_decode($response->getBody(), true)['ok'];
    }

    public function conversations_kick(string $userId, $channelId)
    {
        $response = $this->apiClient
            ->post('https://denhac.slack.com/api/conversations.kick', [
                RequestOptions::FORM_PARAMS => [
                    'channel' => $channelId,
                    'user' => $userId,
                ],
            ]);

        return json_decode($response->getBody(), true)['ok'];
    }

    public function usergroups_list()
    {
        // TODO Make this handle errors/pagination
        return collect(json_decode($this->apiClient
            ->get('https://denhac.slack.com/api/usergroups.list', [
                RequestOptions::QUERY => [
                    'include_users' => true,
                ],
            ])
            ->getBody(), true)["usergroups"]);
    }

    public function usergroupForName($handle)
    {
        return $this->usergroups_list()
            ->firstWhere('handle', $handle);
    }

    public function usergroups_users_update($usergroupId, Collection $users)
    {
        $response = $this->apiClient
            ->post('https://denhac.slack.com/api/usergroups.users.update', [
                RequestOptions::FORM_PARAMS => [
                    'usergroup' => $usergroupId,
                    'users' => $users->implode(","),
                ],
            ]);

        return json_decode($response->getBody(), true)['ok'];
    }
}
