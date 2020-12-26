<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class InviteCustomerPublicOnlyMemberInSlack implements ShouldQueue
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

    /**
     * @throws \Exception
     */
    public function handle()
    {
        if ($this->isExistingSlackUser()) {
            /*
             * This can technically happen if someone signs up on the new system using slack,
             * but honestly I'd rather check all of those manually.
             */
            report(new \Exception("Invite was called on an existing member: {$this->wooCustomerId}"));
        } else {
            $this->inviteSingleChannelGuest('public');
        }
    }
}
