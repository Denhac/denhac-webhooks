<?php

namespace App\Actions\Slack;

use App\Actions\StaticAction;
use App\External\Slack\SlackApi;
use Spatie\QueueableAction\QueueableAction;
use Throwable;

class AddToChannel
{
    use QueueableAction;
    use StaticAction;
    use SlackActionTrait;

    /**
     * @var SlackApi
     */
    private SlackApi $slackApi;

    /**
     * Create a new action instance.
     *
     * @param SlackApi $slackApi
     */
    public function __construct(SlackApi $slackApi)
    {
        $this->slackApi = $slackApi;
    }

    /**
     * Execute the action.
     *
     * @param string $userId The woo customer id or the slack id
     * @param $channel
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
            $response = $this->slackApi->conversations->invite($slackId, $channelId);
        }

        throw new \Exception("Invite of $userId to $channel failed: ".print_r($response, true));
    }
}
