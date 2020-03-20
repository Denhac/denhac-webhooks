<?php

namespace App\Jobs;

use App\Slack\SlackApi;
use App\WooCommerce\Api\ApiCallFailed;
use App\WooCommerce\Api\WooCommerceApi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MakeCustomerPublicOnlyMemberInSlack implements ShouldQueue
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
        $channel_id = collect($slackApi->channelIdsByName('public'))->first();
        // TODO Handle return code?
        $slackApi->users_admin_setUltraRestricted($slack_id, $channel_id);
    }

    protected function membershipType()
    {
        return 'ultra_restricted';
    }

    protected function channelIds(SlackApi $slackApi)
    {
        return $slackApi->channelIdsByName('public');
    }
}
