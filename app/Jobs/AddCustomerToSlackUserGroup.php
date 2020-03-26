<?php

namespace App\Jobs;

use App\Customer;
use App\Slack\SlackApi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AddCustomerToSlackUserGroup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $customerId;
    private $usergroupHandle;

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

        throw_if(is_null($customer->slack_id), "Customer $this->customerId cannot be added to usergroup $this->usergroupHandle with null slack id!");

        $usergroup = $slackApi->usergroupForName($this->usergroupHandle);

        throw_if(is_null($usergroup), "Couldn't find usergroup for $this->usergroupHandle");

        $id = $usergroup["id"];
        $users = collect($usergroup["users"]);
        $users->add($customer->slack_id);

        $slackApi->usergroups_users_update($id, $users);
    }
}
