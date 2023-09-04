<?php

namespace App\Actions\Slack;

use App\Customer;

trait SlackActionTrait
{
    public function slackIdFromGeneralId($id)
    {
        if (is_numeric($id)) {
            /** @var Customer $customer */
            $customer = Customer::whereWooId($id)->first();

            throw_if(is_null($customer), "Customer $id was not found");
            throw_if(is_null($customer->slack_id), "Customer $id has no slack id");

            return $customer->slack_id;
        }

        return $id;
    }

    public function channelIdFromChannel($channel)
    {
        $channels = collect($this->slackApi->conversations->toSlackIds($channel));

        throw_unless($channels->count() == 1, "Expected 1 channel, got {$channels->count()}");

        return $channels->first();
    }
}
