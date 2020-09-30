<?php

namespace App\Actions;

use App\Customer;
use App\Slack\SlackApi;
use Spatie\QueueableAction\QueueableAction;

class AddCustomerToSlackChannel
{
    use QueueableAction;

    /**
     * @var SlackApi
     */
    private $slackApi;

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
     * @param $customerId
     * @param $channel
     */
    public function execute($customerId, $channel)
    {
        /** @var Customer $customer */
        $customer = Customer::whereWooId($customerId)->first();

        throw_if(is_null($customer->slack_id), "Customer $customerId cannot be added to slack channel $channel with null slack id!");

        $channels = $this->slackApi->channelIdsByName($channel);

        throw_unless(count($channels) == 1, "Expected 1 channel 'by name': $channel.");

        $channelId = collect($channels)->first();

        $response = $this->slackApi->conversations_invite($customer->slack_id, $channelId);

        if ($response['ok']) {
            return;
        }

        if ($response['error'] == 'not_in_channel') {
            $this->slackApi->conversations_join($channelId);
            $response = $this->slackApi->conversations_invite($customer->slack_id, $channelId);
        } elseif ($response['error'] == 'already_in_channel') {
            return; // Everything's fine
        }

        $response_s = json_encode($response);
        throw_unless($response['ok'], "Could not join channel $channel: $response_s");
    }
}
