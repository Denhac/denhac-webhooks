<?php

namespace App\Actions;

use App\External\Slack\Channels;
use App\External\Slack\SlackApi;
use App\Models\Customer;
use Spatie\QueueableAction\QueueableAction;

/*
 * Sets the Slack user to be an ultra restricted member, where they can only be part of one channel. This will not
 * invite them to the workspace.
 */

class SetUltraRestrictedUser
{
    use QueueableAction;

    public function __construct(
        private readonly SlackApi $slackApi,
    )
    {
    }

    public function execute(Customer $customer): void
    {
        if (is_null($customer->slack_id)) {
            throw new \Exception("Slack ID was null on customer $customer->id");
        }

        $this->slackApi->users->admin->setUltraRestricted($customer->slack_id, Channels::PUBLIC);
    }
}
