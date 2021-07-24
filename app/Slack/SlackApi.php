<?php

namespace App\Slack;

use App\Slack\api\ChatApi;
use App\Slack\api\ConversationsApi;
use App\Slack\api\SlackClients;
use App\Slack\api\TeamApi;
use App\Slack\api\UsergroupsApi;
use App\Slack\api\UsersApi;
use App\Slack\api\ViewsApi;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use JetBrains\PhpStorm\Pure;

/**
 * @property ChatApi chat
 * @property ConversationsApi conversations
 * @property TeamApi team
 * @property UsergroupsApi usergroups
 * @property UsersApi users
 * @property ViewsApi views
 */
class SlackApi
{
    public const PUBLIC_CHANNEL = 'public_channel';
    public const PRIVATE_CHANNEL = 'private_channel';
    public const MULTI_PARTY_MESSAGE = 'mpim';
    public const DIRECT_MESSAGE = 'im';

    private const ADMIN_TOKEN_CACHE_KEY = 'slack.admin.token';
    /**
     * @var Client
     * This one is for the denhac management app.
     */
    private $managementApiClient;
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
        if ($name == 'chat') {
            return new ChatApi($this->clients);
        } else if ($name == 'conversations') {
            return new ConversationsApi($this->clients);
        } else if ($name == 'team') {
            return new TeamApi($this->clients);
        } else if ($name == 'usergroups') {
            return new UsergroupsApi($this->clients);
        } else if ($name == 'users') {
            return new UsersApi($this->clients);
        } else if ($name == 'views') {
            return new ViewsApi($this->clients);
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

    public function usergroupForName($handle)
    {
        return $this->usergroups->list()
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
}
