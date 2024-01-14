<?php

namespace App\Reactors;

use App\Models\Customer;
use App\Models\VolunteerGroup;
use App\StorableEvents\WooCommerce\UserMembershipCreated;
use App\StorableEvents\WooCommerce\UserMembershipUpdated;
use Spatie\EventSourcing\EventHandlers\Reactors\Reactor;

class VolunteerGroupsReactor extends Reactor
{
    public function onUserMembershipCreated(UserMembershipCreated $event): void
    {
        $planId = $event->membership['plan_id'];
        $customerId = $event->membership['customer_id'];
        $customer = Customer::find($customerId);

        $volunteerGroups = VolunteerGroup::wherePlanId($planId)->with('channels')->get();
        foreach ($volunteerGroups as $volunteerGroup) {
            /** @var VolunteerGroup $volunteerGroup */
            $volunteerGroup->channels()->each(fn($ch) => $ch->add($customer));
        }
    }

    public function onUserMembershipUpdated(UserMembershipUpdated $event): void
    {
        $status = $event->membership['status'];
        $planId = $event->membership['plan_id'];
        $customerId = $event->membership['customer_id'];
        $customer = Customer::find($customerId);

        $volunteerGroups = VolunteerGroup::wherePlanId($planId)->with('channels')->get();
        foreach ($volunteerGroups as $volunteerGroup) {
            /** @var VolunteerGroup $volunteerGroup */

            if (in_array($status, ['paused', 'expired', 'cancelled'])) {
                $volunteerGroup->channels()->each(fn($ch) => $ch->remove($customer));
            } elseif (in_array($status, ['active', 'free_trial', 'pending'])) {
                $volunteerGroup->channels()->each(fn($ch) => $ch->add($customer));
            }
        }
    }
}
