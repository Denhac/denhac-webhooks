<?php

namespace App\Reactors;

use App\Actions\AddUserToOctoPrintHosts;
use App\Customer;
use App\StorableEvents\MembershipActivated;
use App\StorableEvents\UserMembershipCreated;
use App\UserMembership;
use Spatie\EventSourcing\EventHandlers\EventHandler;
use Spatie\EventSourcing\EventHandlers\HandlesEvents;

class OctoPrintReactor implements EventHandler
{
    use HandlesEvents;

    public function onUserMembershipCreated(UserMembershipCreated $event)
    {
        if($event->membership['plan_id'] != UserMembership::MEMBERSHIP_3DP_USER) {
            return;
        }
        if($event->membership['status'] != 'active') {
            return;
        }

        /** @var Customer $customer */
        $customer = Customer::whereWooId($event->membership['customer_id'])->first();

        if(!$customer->member) {
            return;
        }

        app(AddUserToOctoPrintHosts::class)
            ->onQueue('event-sourcing')
            ->execute($customer);
    }

    public function onMembershipActivated(MembershipActivated $event)
    {
        /** @var Customer $customer */
        $customer = Customer::whereWooId($event->customerId)->first();

        if(! $customer->hasMembership(UserMembership::MEMBERSHIP_3DP_USER)) {
            return;
        }

        app(AddUserToOctoPrintHosts::class)
            ->onQueue('event-sourcing')
            ->execute($customer);
    }

    // TODO Membership deactivated deactivates octoprint
    // TODO Trainer is added to 3dp@denhac.org
    // TODO Trainer is added to 3dp_team slack group
    // TODO Member is added to #3d-printing slack
}
