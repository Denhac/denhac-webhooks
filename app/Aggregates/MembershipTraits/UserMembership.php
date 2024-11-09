<?php

namespace App\Aggregates\MembershipTraits;

use App\StorableEvents\WooCommerce\UserMembershipCreated;
use App\StorableEvents\WooCommerce\UserMembershipImported;
use App\StorableEvents\WooCommerce\UserMembershipUpdated;

trait UserMembership
{
    public bool $activeFullMemberPlan = false;

    public array $userMembershipIdToPlanId;

    public function bootUserMembership(): void
    {
        $this->userMembershipIdToPlanId = [];
    }

    public function handleUserMembership($userMembership): void
    {
        if ($userMembership['plan_id'] == \App\Models\UserMembership::MEMBERSHIP_FULL_MEMBER) {
            $this->handleFullMemberPlan($userMembership);
        }
    }

    public function handleUserMembershipDeleted($userMembershipId): void
    {
        if (! array_key_exists($userMembershipId, $this->userMembershipIdToPlanId)) {
            // This shouldn't happen, but we'd like to report the exception just in case so someone can investigate.
            throw new \Exception("Did not find deleted user membership: $userMembershipId");
        }

        $planId = $this->userMembershipIdToPlanId[$userMembershipId];
        if ($planId == \App\Models\UserMembership::MEMBERSHIP_FULL_MEMBER) {
            $this->deactivateMembershipIfNeeded();
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
        $this->userMembershipIdToPlanId[$userMembership['id']] = $userMembership['plan_id'];

        if ($userMembership['plan_id'] == \App\Models\UserMembership::MEMBERSHIP_FULL_MEMBER) {
            if ($userMembership['status'] == 'active') {
                $this->activeFullMemberPlan = true;
            } else {
                $this->activeFullMemberPlan = false;
            }
        }
    }
}
