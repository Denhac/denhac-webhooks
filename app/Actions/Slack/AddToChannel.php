<?php

namespace App\Actions\Slack;

use App\Actions\StaticAction;
use App\External\Slack\SlackApi;
use App\External\Slack\SlackRateLimit;
use Spatie\QueueableAction\QueueableAction;
use Throwable;

class AddToChannel
{
    use QueueableAction;
    use SlackActionTrait;
    use StaticAction;

    public string $queue = 'slack-rate-limited';

    private SlackApi $slackApi;

    /**
     * Create a new action instance.
     */
    public function __construct(SlackApi $slackApi)
    {
        $this->slackApi = $slackApi;
    }

    /**
     * Execute the action.
     *
     * @param  string  $userId  The woo customer id or the slack id
     *
     * @throws Throwable
     */
    public function execute(string $userId, $channel)
    {
        $slackId = $this->slackIdFromGeneralId($userId);
        $channelId = $this->channelIdFromChannel($channel);

        $response = $this->slackApi->conversations->invite($slackId, $channelId);

        if ($response['ok']) {
            return;
        }

        if ($response['error'] == 'already_in_channel') {
            return; // Everything is fine, they're already in the channel
        }

        if ($response['error'] == 'not_in_channel') {
            $this->slackApi->conversations->join($channelId);
            // Now that we're in the channel, we can safely re-queue the job and invite someone else
            AddToChannel::queue()->execute($userId, $channelId);

            return;
        }

        throw new \Exception("Invite of $userId to $channel failed: ".print_r($response, true));
    }

    public function middleware()
    {
        return [
            SlackRateLimit::conversations_list(),
            SlackRateLimit::conversations_invite(),
            SlackRateLimit::conversations_join(),
        ];
    }
}
