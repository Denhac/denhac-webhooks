<?php

namespace App\Projectors;

use App\StorableEvents\WooCommerce\CustomerDeleted;
use App\StorableEvents\WooCommerce\UserMembershipCreated;
use App\StorableEvents\WooCommerce\UserMembershipDeleted;
use App\StorableEvents\WooCommerce\UserMembershipImported;
use App\StorableEvents\WooCommerce\UserMembershipUpdated;
use App\Models\UserMembership;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;
use Spatie\EventSourcing\EventHandlers\Projectors\ProjectsEvents;

class UserMembershipProjector extends Projector
{
    use ProjectsEvents;

    public function onUserMembershipCreated(UserMembershipCreated $event)
    {
        $this->addOrGetUserMembership($event->membership);
    }

    public function onUserMembershipImported(UserMembershipImported $event)
    {
        $this->addOrGetUserMembership($event->membership);
    }

    public function onUserMembershipUpdated(UserMembershipUpdated $event)
    {
        $membership = $this->addOrGetUserMembership($event->membership);

        $membership->customer_id = $event->membership['customer_id'];
        $membership->plan_id = $event->membership['plan_id'];
        $membership->status = $event->membership['status'];

        $membership->save();
    }

    public function onUserMembershipDeleted(UserMembershipDeleted $event)
    {
        $membership = UserMembership::find($event->membership['id']);

        if (! is_null($membership)) {
            $membership->delete();
        }
    }

    public function onCustomerDeleted(CustomerDeleted $event)
    {
        UserMembership::whereCustomerId($event->customerId)
            ->delete();
    }

    private function addOrGetUserMembership(array $membership_json)
    {
        /** @var UserMembership $userMembership */
        $userMembership = UserMembership::find($membership_json['id']);

        if (is_null($userMembership)) {
            return UserMembership::create([
                'id' => $membership_json['id'],
                'plan_id' => $membership_json['plan_id'],
                'customer_id' => $membership_json['customer_id'],
                'status' => $membership_json['status'],
            ]);
        } else {
            return $userMembership;
        }
    }
}
