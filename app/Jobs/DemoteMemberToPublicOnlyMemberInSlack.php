<?php

namespace App\Jobs;

use App\External\Slack\Channels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DemoteMemberToPublicOnlyMemberInSlack implements ShouldQueue
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

    public function handle()
    {
        if ($this->isExistingSlackUser()) {
            $this->setSingleChannelGuest(Channels::PUBLIC);
        } else {
            report(new \Exception("Demote was called on a new member: {$this->wooCustomerId}"));
        }
    }
}
