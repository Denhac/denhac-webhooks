<?php

namespace App\Actions\Slack;

use App\Actions\StaticAction;
use App\External\Slack\SlackApi;
use App\External\Slack\SlackRateLimit;
use Spatie\QueueableAction\QueueableAction;

class RemoveFromChannel
{
    use QueueableAction;
    use SlackActionTrait;
    use StaticAction;

    public string $queue = 'slack-rate-limited';

    private SlackApi $slackApi;

    public function __construct(SlackApi $slackApi)
    {
        $this->slackApi = $slackApi;
    }

    public function execute($userId, $channel)
    {
        $slackId = $this->slackIdFromGeneralId($userId);
        $channelId = $this->channelIdFromChannel($channel);

        $response = $this->slackApi->conversations->kick($slackId, $channelId);

        if ($response['ok']) {
            return;
        } elseif ($response['error'] == 'not_in_channel') {
            return; // Everything's fine, user isn't in channel
        }

        throw new \Exception("Kick of $userId from $channel failed: ".print_r($response, true));
    }

    public function middleware()
    {
        return [
            SlackRateLimit::conversations_list(),
            SlackRateLimit::conversations_kick(),
        ];
    }
}
