<?php

namespace App\Reactors;

use App\Models\Customer;
use App\Notifications\LaserCutterAccessAllowed;
use App\Models\UserMembership;
use App\StorableEvents\WooCommerce\UserMembershipCreated;
use Spatie\EventSourcing\EventHandlers\EventHandler;
use Spatie\EventSourcing\EventHandlers\HandlesEvents;

class LaserCutterReactor implements EventHandler
{
    use HandlesEvents;

    public function onUserMembershipCreated(UserMembershipCreated $event)
    {
        if ($event->membership['plan_id'] != UserMembership::MEMBERSHIP_LASER_CUTTER_USER) {
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

        $customer->notify(new LaserCutterAccessAllowed($customer));
    }
}
