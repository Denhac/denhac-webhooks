<?php

namespace App\Jobs;

use App\Actions\Slack\RemoveFromChannel;
use App\External\Slack\Channels;
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
     */
    public function __construct($wooCustomerId)
    {
        $this->wooCustomerId = $wooCustomerId;
    }

    public function handle()
    {
        if ($this->isExistingSlackUser()) {
            $this->setRegularMember();

            /** @var RemoveFromChannel $removeFromChannel */
            $removeFromChannel = app(RemoveFromChannel::class);
            $removeFromChannel->execute($this->customerSlackId, Channels::NEED_ID_CHECK);
        } else {
            $this->inviteRegularMember(['general', 'public', 'random']);
        }
    }
}
