<?php

namespace App\Jobs;

use App\Slack\SlackApi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MakeCustomerRegularMemberInSlack implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    use MakeCustomerMemberInSlackMixin;

    /**
     * Create a new job instance.
     *
     * @param $wooCustomerId
     */
    public function __construct($wooCustomerId)
    {
        $this->wooCustomerId = $wooCustomerId;
    }

    protected function handleExistingMember(SlackApi $slackApi, $slack_id)
    {
        // TODO Handle return code?
        $slackApi->users_admin_setRegular($slack_id);
        // TODO Invite them back to general/public/random?
    }

    protected function membershipType()
    {
        return "regular";
    }

    protected function channelIds(SlackApi $slackApi)
    {
        return $slackApi->channelIdsByName(["general", "public", "random"]);
    }
}
