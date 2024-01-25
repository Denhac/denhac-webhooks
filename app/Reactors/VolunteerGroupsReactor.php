<?php

namespace App\Reactors;

use App\Models\Customer;
use App\Models\UserMembership;
use App\Models\VolunteerGroup;
use App\Models\VolunteerGroupChannel;
use App\StorableEvents\Membership\MembershipDeactivated;
use App\StorableEvents\WooCommerce\UserMembershipCreated;
use App\StorableEvents\WooCommerce\UserMembershipDeleted;
use App\StorableEvents\WooCommerce\UserMembershipUpdated;
use Spatie\EventSourcing\EventHandlers\Reactors\Reactor;

class VolunteerGroupsReactor extends Reactor
{
    public function onUserMembershipCreated(UserMembershipCreated $event): void
    {
        $planId = $event->membership['plan_id'];
        $customerId = $event->membership['customer_id'];
        $customer = Customer::find($customerId);

        $volunteerGroup = VolunteerGroup::wherePlanId($planId)->with('channels')->first();

        if(is_null($volunteerGroup)) {
            return;
        }

        /** @var VolunteerGroup $volunteerGroup */
        $volunteerGroup->channels()->each(fn($ch) => $ch->add($customer));
    }

    public function onUserMembershipUpdated(UserMembershipUpdated $event): void
    {
        $status = $event->membership['status'];
        $planId = $event->membership['plan_id'];
        $customerId = $event->membership['customer_id'];
        $customer = Customer::find($customerId);

        $volunteerGroup = VolunteerGroup::wherePlanId($planId)->with('channels')->first();
        if(is_null($volunteerGroup)) {
            return;
        }

        /** @var VolunteerGroup $volunteerGroup */

        if (in_array($status, ['paused', 'expired', 'cancelled'])) {
            $volunteerGroup->channels()->each(fn($ch) => $ch->remove($customer));
        } elseif (in_array($status, ['active', 'free_trial', 'pending'])) {
            $volunteerGroup->channels()->each(fn($ch) => $ch->add($customer));
        }
    }

    public function onUserMembershipDeleted(UserMembershipDeleted $event)
    {
        $userMembershipId = $event->membership['id'];
        /** @var UserMembership $userMembership */
        // The UserMembership projector soft deleted this, so to access the plan id we need to use withTrashed()
        $userMembership = UserMembership::withTrashed()->find($userMembershipId);
        $customer = $userMembership->customer;

        $volunteerGroup = VolunteerGroup::wherePlanId($userMembership->plan_id)->with('channels')->first();
        if(is_null($volunteerGroup)) {
            return;
        }

        /** @var VolunteerGroup $volunteerGroup */
        $volunteerGroup->channels()->each(fn($ch) => $ch->remove($customer));
    }

    public function onMembershipDeactivated(MembershipDeactivated $event): void
    {
        $customerId = $event->customerId;
        /** @var Customer $customer */
        $customer = Customer::find($customerId);

        $userMembershipIds = $customer->memberships->map(fn($um) => $um->plan_id)->toArray();
        $volunteerGroups = VolunteerGroup::whereIn('plan_id', $userMembershipIds)->with('channels')->get();

        foreach ($volunteerGroups as $volunteerGroup) {
            /** @var VolunteerGroup $volunteerGroup */

            foreach ($volunteerGroup->channels as $channel) {
                /** @var VolunteerGroupChannel $channel */
                if($channel->removeOnMembershipLost()) {
                    $channel->remove($customer);
                }
            }
        }
    }
}
