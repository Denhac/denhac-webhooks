<?php

namespace App\Aggregates\MembershipTraits;


use App\FeatureFlags;
use App\StorableEvents\UserMembershipCreated;
use App\StorableEvents\UserMembershipImported;
use App\StorableEvents\UserMembershipUpdated;
use Illuminate\Support\Collection;
use YlsIdeas\FeatureFlags\Facades\Features;

trait UserMembership
{
    public Collection $userMembershipPreviousStatus;
    public Collection $userMembershipCurrentStatus;
    public bool $activeFullMemberPlan = false;

    public function bootUserMembership()
    {
        $this->userMembershipPreviousStatus = collect();
        $this->userMembershipCurrentStatus = collect();
    }

    public function handleUserMembership($userMembership)
    {
        if ($userMembership['plan_id'] == \App\UserMembership::MEMBERSHIP_FULL_MEMBER) {
            $this->handleFullMemberPlan($userMembership);
        }
    }

    /**
     * @param $userMembership
     * @return void
     */
    protected function handleFullMemberPlan($userMembership): void
    {
        $id = $userMembership['id'];

        $oldStatus = $this->userMembershipPreviousStatus->get($id);
        $currentStatus = $userMembership['status'];

        $neededIdCheck = in_array($oldStatus, ['need-id-check', 'id-was-checked']);
        if ($neededIdCheck && $currentStatus == 'active' && $this->idWasChecked) {
            if (Features::accessible(FeatureFlags::USER_MEMBERSHIP_CONTROLS_ACTIVE)) {
                $this->activateMembership();
            }
        }

        if ($currentStatus == 'cancelled') {
            if (Features::accessible(FeatureFlags::USER_MEMBERSHIP_CONTROLS_ACTIVE)) {
                $this->deactivateMembership();
            }
        }

        $this->userMembershipPreviousStatus->put($id, $currentStatus);
    }

    protected function applyUserMembershipCreated(UserMembershipCreated $event)
    {
        $this->updateUserMembershipStatus($event->membership);
    }

    protected function applyUserMembershipUpdated(UserMembershipUpdated $event)
    {
        $this->updateUserMembershipStatus($event->membership);
    }

    protected function applyUserMembershipImported(UserMembershipImported $event)
    {
        $this->updateUserMembershipStatus($event->membership);
    }

    protected function updateUserMembershipStatus($userMembership)
    {
        $id = $userMembership['id'];
        $planId = $userMembership['plan_id'];
        $newStatus = $userMembership['status'];

        if ($newStatus == 'active' && $planId == \App\UserMembership::MEMBERSHIP_FULL_MEMBER) { // && UserMembership::FULL_MEMBER
            $this->activeFullMemberPlan = true;
        }

        // This gets called before our handle user membership status so our old status is our new status above
        $oldStatus = $this->userMembershipCurrentStatus->get($id);
        $this->userMembershipPreviousStatus->put($id, $oldStatus);
        $this->userMembershipCurrentStatus->put($id, $newStatus);
    }
}
