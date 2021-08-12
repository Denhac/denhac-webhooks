<?php

namespace App\Reactors;

use App\Actions\AddUserToOctoPrintHosts;
use App\Actions\DeactivateOctoPrintUser;
use App\Customer;
use App\StorableEvents\MembershipActivated;
use App\StorableEvents\MembershipDeactivated;
use App\StorableEvents\UserMembershipCreated;
use App\UserMembership;
use Spatie\EventSourcing\EventHandlers\EventHandler;
use Spatie\EventSourcing\EventHandlers\HandlesEvents;

class OctoPrintReactor implements EventHandler
{
    use HandlesEvents;

    public function onUserMembershipCreated(UserMembershipCreated $event)
    {
        if ($event->membership['plan_id'] != UserMembership::MEMBERSHIP_3DP_USER) {
            return;
        }
        if ($event->membership['status'] != 'active') {
            return;
        }

        /** @var Customer $customer */
        $customer = Customer::whereWooId($event->membership['customer_id'])->first();

        if (! $customer->member) {
            return;
        }

        AddUserToOctoPrintHosts::queue()->execute($customer);
    }

    public function onMembershipActivated(MembershipActivated $event)
    {
        /** @var Customer $customer */
        $customer = Customer::whereWooId($event->customerId)->first();

        if (! $customer->hasMembership(UserMembership::MEMBERSHIP_3DP_USER)) {
            return;
        }

        AddUserToOctoPrintHosts::queue()->execute($customer);
    }

    public function onMembershipDeactivated(MembershipDeactivated $event)
    {
        /** @var Customer $customer */
        $customer = Customer::whereWooId($event->customerId)->first();

        if (! $customer->hasMembership(UserMembership::MEMBERSHIP_3DP_USER)) {
            return;
        }

        DeactivateOctoPrintUser::queue()->execute($customer);
    }
}
