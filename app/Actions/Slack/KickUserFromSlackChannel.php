<?php

namespace App\Actions\Slack;

use App\Slack\SlackApi;
use Spatie\QueueableAction\QueueableAction;

class KickUserFromSlackChannel
{
    use QueueableAction;
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

        $response = $this->slackApi->conversations_kick($slackId, $channelId);

        if ($response['ok']) {
            return;
        }

        throw new \Exception("Kick of $userId from $channel failed: ".print_r($response, true));

//        if ($response['error'] == 'not_in_channel') {
//            $this->slackApi->conversations_join($channelId);
//            $response = $this->slackApi->conversations_kick($customerId, $channelId);
//        } elseif ($response['error'] == 'already_in_channel') {
//            return; // Everything's fine
//        }
//
//        $response_s = json_encode($response);
//        throw_unless($response['ok'], "Could not join channel $channel: $response_s");
    }
}
