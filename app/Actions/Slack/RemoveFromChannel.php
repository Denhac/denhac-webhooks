<?php

namespace App\Actions\Slack;

use App\Actions\StaticAction;
use App\Slack\SlackApi;
use Spatie\QueueableAction\QueueableAction;

class RemoveFromChannel
{
    use QueueableAction;
    use StaticAction;
    use SlackActionTrait;

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
        } else if ($response['error'] == 'not_in_channel') {
            return; // Everything's fine, user isn't in channel
        }

        throw new \Exception("Kick of $userId from $channel failed: ".print_r($response, true));
    }
}
