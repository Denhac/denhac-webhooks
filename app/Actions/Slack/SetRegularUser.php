<?php

namespace App\Actions\Slack;

use App\External\Slack\Channels;
use App\External\Slack\SlackApi;
use App\Models\Customer;
use Spatie\QueueableAction\QueueableAction;

/*
 * Sets the Slack user to be a regular member. This will not invite them to the workspace.
 */

class SetRegularUser
{
    use QueueableAction;

    public function __construct(
        private readonly SlackApi $slackApi,
        private readonly RemoveFromChannel $removeFromChannel,
        private readonly AddToChannel $addToChannel
    ) {}

    public function execute(Customer $customer): void
    {
        if (is_null($customer->slack_id)) {
            throw new \Exception("Slack ID was null on customer $customer->id");
        }

        $this->slackApi->users->admin->setRegular($customer->slack_id);

        $this->addToChannel->execute($customer->slack_id, Channels::GENERAL);
        $this->addToChannel->execute($customer->slack_id, Channels::PUBLIC);
        $this->addToChannel->execute($customer->slack_id, Channels::RANDOM);
        $this->removeFromChannel->execute($customer->slack_id, Channels::NEED_ID_CHECK);
    }
}
