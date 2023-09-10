<?php

namespace App\Reactors;

use App\Models\Customer;
use App\StorableEvents\MembershipActivated;
use App\StorableEvents\MembershipDeactivated;
use App\StorableEvents\WooCommerce\UserMembershipCreated;
use App\Models\UserMembership;
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

        if (!$customer->member) {
            return;
        }

        // Currently doesn't work because the webhooks server cannot access these hosts
        // AddUserToOctoPrintHosts::queue()->execute($customer);
    }

    public function onMembershipActivated(MembershipActivated $event)
    {
        /** @var Customer $customer */
        $customer = Customer::whereWooId($event->customerId)->first();

        if (!$customer->hasMembership(UserMembership::MEMBERSHIP_3DP_USER)) {
            return;
        }

        // Currently doesn't work because the webhooks server cannot access these hosts
        // AddUserToOctoPrintHosts::queue()->execute($customer);
    }

    public function onMembershipDeactivated(MembershipDeactivated $event)
    {
        /** @var Customer $customer */
        $customer = Customer::whereWooId($event->customerId)->first();

        if (!$customer->hasMembership(UserMembership::MEMBERSHIP_3DP_USER)) {
            return;
        }

        // Currently doesn't work because the webhooks server cannot access these hosts
        // DeactivateOctoPrintUser::queue()->execute($customer);
    }
}
