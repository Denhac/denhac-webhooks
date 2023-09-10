<?php

namespace App\Aggregates\MembershipTraits;


use App\StorableEvents\WooCommerce\UserMembershipCreated;
use App\StorableEvents\WooCommerce\UserMembershipImported;
use App\StorableEvents\WooCommerce\UserMembershipUpdated;

trait UserMembership
{
    public bool $activeFullMemberPlan = false;

    public function handleUserMembership($userMembership): void
    {
        if ($userMembership['plan_id'] == \App\Models\UserMembership::MEMBERSHIP_FULL_MEMBER) {
            $this->handleFullMemberPlan($userMembership);
        }
    }

    protected function handleFullMemberPlan($userMembership): void
    {
        $currentStatus = $userMembership['status'];

        if ($currentStatus == 'active' && $this->idWasChecked) {
            $this->activateMembershipIfNeeded();
        }

        if (in_array($currentStatus, ['cancelled', 'expired'])) {
            $this->deactivateMembershipIfNeeded();
        }
    }

    protected function applyUserMembershipCreated(UserMembershipCreated $event): void
    {
        $this->updateUserMembershipStatus($event->membership);
    }

    protected function applyUserMembershipUpdated(UserMembershipUpdated $event): void
    {
        $this->updateUserMembershipStatus($event->membership);
    }

    protected function applyUserMembershipImported(UserMembershipImported $event): void
    {
        $this->updateUserMembershipStatus($event->membership);
    }

    protected function updateUserMembershipStatus($userMembership): void
    {
        if ($userMembership['plan_id'] == \App\Models\UserMembership::MEMBERSHIP_FULL_MEMBER) {
            if ($userMembership['status'] == 'active') {
                $this->activeFullMemberPlan = true;
            } else {
                $this->activeFullMemberPlan = false;
            }
        }
    }
}
