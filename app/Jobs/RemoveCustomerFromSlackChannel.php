<?php

namespace App\Jobs;

use App\Customer;
use App\Slack\SlackApi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RemoveCustomerFromSlackChannel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $customerId;
    public $channel;

    /**
     * Create a new job instance.
     *
     * @param $customerId
     * @param $channel
     */
    public function __construct($customerId, $channel)
    {
        $this->customerId = $customerId;
        $this->channel = $channel;
    }

    /**
     * Execute the job.
     *
     * @param SlackApi $slackApi
     * @return void
     * @throws \Throwable
     */
    public function handle(SlackApi $slackApi)
    {
        /** @var Customer $customer */
        $customer = Customer::whereWooId($this->customerId)->first();

        throw_if(is_null($customer->slack_id), "Customer $this->customerId cannot be removed from slack channel $this->channel with null slack id!");

        $channels = $slackApi->channelIdsByName($this->channel);

        throw_unless(count($channels) == 1, "Expected 1 channel 'by name': $this->channel.");

        $channelId = collect($channels)->first();

        $slackApi->conversations_kick($customer->slack_id, $channelId);
    }
}
