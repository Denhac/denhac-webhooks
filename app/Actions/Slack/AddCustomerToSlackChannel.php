<?php

namespace App\Actions\Slack;

use App\Customer;
use App\Slack\SlackApi;
use Spatie\QueueableAction\QueueableAction;
use Throwable;

class AddCustomerToSlackChannel
{
    use QueueableAction;
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

        $response = $this->slackApi->conversations_invite($slackId, $channelId);

        if ($response['ok']) {
            return;
        }

        throw new \Exception("Invite of $userId to $channel failed: ".print_r($response, true));

//        if ($response['error'] == 'not_in_channel') {
//            $this->slackApi->conversations_join($channelId);
//            $response = $this->slackApi->conversations_invite($customerId, $channelId);
//        } elseif ($response['error'] == 'already_in_channel') {
//            return; // Everything's fine
//        }
//
//        $response_s = json_encode($response);
//        throw_unless($response['ok'], "Could not join channel $channel: $response_s");
    }
}
