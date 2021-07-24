<?php

namespace App\Slack;

use App\Slack\api\SlackClients;
use App\Slack\api\UsersApi;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Jeremeamia\Slack\BlockKit\Surfaces\Message;
use JetBrains\PhpStorm\Pure;

/**
 * @property UsersApi users
 */
class SlackApi
{
    private const ADMIN_TOKEN_CACHE_KEY = 'slack.admin.token';
    /**
     * @var Client
     * This one is for the denhac management app.
     */
    private $managementApiClient;
    /**
     * @var Client
     * This one is for the spacebot bot client.
     */
    private $spaceBotApiClient;
    /**
     * @var Client
     */
    private $adminClient;

    private SlackClients $clients;

    public function __construct()
    {
        $this->clients = new SlackClients();
        $managementApiToken = config('denhac.slack.management_api_token');
        $this->managementApiClient = new Client([
            RequestOptions::HEADERS => [
                'Authorization' => "Bearer $managementApiToken",
            ],
        ]);
        $spaceBotApiToken = config('denhac.slack.spacebot_api_token');
        $this->spaceBotApiClient = new Client([
            RequestOptions::HEADERS => [
                'Authorization' => "Bearer $spaceBotApiToken",
            ],
        ]);
    }

    private function ensureAdminClient()
    {
        if (is_null($this->adminClient)) {
            $this->adminClient = $this->getAdminClient(
                config('denhac.slack.email'),
                config('denhac.slack.password')
            );
        }
    }

    private function getAdminClient($email, $password)
    {
        $token = setting(self::ADMIN_TOKEN_CACHE_KEY);

        if (is_null($token) || !$this->isValidToken($token)) {
            $token = $this->makeAdminToken($email, $password);
            setting([self::ADMIN_TOKEN_CACHE_KEY => $token])->save();
        }

        return new Client([
            RequestOptions::HEADERS => [
                'Authorization' => "Bearer $token",
            ],
        ]);
    }

    private function paginate($key, $request)
    {
        $cursor = "";
        $collection = collect();
        do {
            $response = json_decode($request($cursor)->getBody(), true);
            if (array_key_exists($key, $response)) {
                $collection = $collection->merge($response[$key]);
            } else {
                return collect($response);
            }

            if (!array_key_exists("response_metadata", $response)) break;
            if (!array_key_exists("next_cursor", $response["response_metadata"])) break;

            $cursor = $response["response_metadata"]["next_cursor"];
        } while ($cursor != "");

        return $collection;
    }

    #[Pure] public function __get(string $name)
    {
        if ($name == 'users') {
            return new UsersApi($this->clients);
        }

        return null;
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
        $this->ensureAdminClient();

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
        $this->ensureAdminClient();

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
        $this->ensureAdminClient();

        $response = $this->adminClient
            ->post('https://denhac.slack.com/api/users.admin.setUltraRestricted', [
                RequestOptions::FORM_PARAMS => [
                    'user' => $slack_id,
                    'channel' => $channel_id,
                ],
            ]);

        return json_decode($response->getBody(), true)['ok'];
    }

    public function users_lookupByEmail($email)
    {
        // TODO Handle user not found/ok is false
        $response = $this->managementApiClient->get('https://denhac.slack.com/api/users.lookupByEmail', [
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

    public function channels_list()
    {
        return $this->paginate('channels', function ($cursor) {
            return $this->managementApiClient
                ->get('https://denhac.slack.com/api/conversations.list', [
                    RequestOptions::QUERY => [
                        'types' => 'public_channel,private_channel',
                        'cursor' => $cursor,
                    ],
                ]);
        });
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
    public function channels($wantedChannels): array
    {
        $wantedChannels = Arr::wrap($wantedChannels);

        return $this->channels_list()
            ->filter(fn($ch) => (
                in_array($ch['id'], $wantedChannels) || in_array($ch['name'], $wantedChannels)
            ))
            ->map(fn($channel) => $channel['id'])
            ->values()
            ->unique()
            ->all();
    }

    public function conversations_join($channelId)
    {
        $response = $this->managementApiClient
            ->post('https://denhac.slack.com/api/conversations.join', [
                RequestOptions::FORM_PARAMS => [
                    'channel' => $channelId,
                ],
            ]);

        return json_decode($response->getBody(), true);
    }

    public function conversations_invite(string $userId, $channelId)
    {
        $response = $this->managementApiClient
            ->post('https://denhac.slack.com/api/conversations.invite', [
                RequestOptions::FORM_PARAMS => [
                    'channel' => $channelId,
                    'users' => $userId,
                ],
            ]);

        return json_decode($response->getBody(), true);
    }

    public function conversations_kick(string $userId, $channelId)
    {
        $response = $this->managementApiClient
            ->post('https://denhac.slack.com/api/conversations.kick', [
                RequestOptions::FORM_PARAMS => [
                    'channel' => $channelId,
                    'user' => $userId,
                ],
            ]);

        return json_decode($response->getBody(), true);
    }

    public function chat_postMessage($conversationId, Message $message)
    {
        return $this->spaceBotApiClient
            ->post('https://denhac.slack.com/api/chat.postMessage', [
                RequestOptions::JSON => [
                    'channel' => $conversationId,
                    'blocks' => json_encode($message->getBlocks()),
                ],
            ]);
    }

    public function team_accessLogs()
    {
        $this->ensureAdminClient();

        $response = $this->adminClient
            ->get('https://denhac.slack.com/api/team.accessLogs');

        return json_decode($response->getBody(), true)['logins'];
    }

    public function usergroups_list()
    {
        // TODO Make this handle errors/pagination
        return collect(json_decode($this->managementApiClient
            ->get('https://denhac.slack.com/api/usergroups.list', [
                RequestOptions::QUERY => [
                    'include_users' => true,
                ],
            ])
            ->getBody(), true)['usergroups']);
    }

    public function usergroupForName($handle)
    {
        return $this->usergroups_list()
            ->firstWhere('handle', $handle);
    }

    public function usergroups_users_update($usergroupId, Collection $users)
    {
        $this->ensureAdminClient();

        $response = $this->adminClient
            ->post('https://denhac.slack.com/api/usergroups.users.update', [
                RequestOptions::FORM_PARAMS => [
                    'usergroup' => $usergroupId,
                    'users' => $users->implode(','),
                ],
            ]);

        return json_decode($response->getBody(), true)['ok'];
    }

    public function views_open($trigger_id, $view)
    {
        return $this->spaceBotApiClient
            ->post('https://denhac.slack.com/api/views.open', [
                RequestOptions::JSON => [
                    'trigger_id' => $trigger_id,
                    'view' => json_encode($view),
                ],
            ]);
    }

    public function views_publish($user_id, $view)
    {
        $this->spaceBotApiClient
            ->post('https://denhac.slack.com/api/views.publish', [
                RequestOptions::JSON => [
                    'user_id' => $user_id,
                    'view' => json_encode($view),
                ],
            ]);
    }

    public function user_profile_set($user_id, $profile)
    {
        $this->ensureAdminClient();

        return $this->adminClient
            ->post('https://denhac.slack.com/api/users.profile.set', [
                RequestOptions::JSON => [
                    'user' => $user_id,
                    'profile' => $profile,
                ],
            ]);
    }
}
