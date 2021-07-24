<?php

namespace App\Jobs;

use App\Customer;
use App\Slack\SlackApi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RemoveCustomerFromSlackUserGroup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $customerId;
    public $usergroupHandle;

    /**
     * Create a new job instance.
     *
     * @param $customerId
     * @param $usergroupHandle
     */
    public function __construct($customerId, $usergroupHandle)
    {
        $this->customerId = $customerId;
        $this->usergroupHandle = $usergroupHandle;
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

        throw_if(is_null($customer->slack_id), "Customer $this->customerId cannot be removed from usergroup $this->usergroupHandle with null slack id!");

        $usergroup = $slackApi->usergroups->byName($this->usergroupHandle);

        throw_if(is_null($usergroup), "Couldn't find usergroup for $this->usergroupHandle");

        $id = $usergroup['id'];
        $users = collect($usergroup['users'])
            ->filter(function ($user_id) use ($customer) {
                return $user_id != $customer->slack_id;
            });

        $slackApi->usergroups->users->update($id, $users);
    }
}
