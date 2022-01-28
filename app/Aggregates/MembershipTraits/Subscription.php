<?php

namespace App\Aggregates\MembershipTraits;

use App\FeatureFlags;
use App\StorableEvents\SubscriptionCreated;
use App\StorableEvents\SubscriptionImported;
use App\StorableEvents\SubscriptionUpdated;
use YlsIdeas\FeatureFlags\Facades\Features;

trait Subscription
{
    public $subscriptionsOldStatus;
    public $subscriptionsNewStatus;

    public function bootSubscription()
    {
        $this->subscriptionsOldStatus = collect();
        $this->subscriptionsNewStatus = collect();
    }

    public function handleSubscriptionStatus($subscriptionId, $newStatus)
    {
        $oldStatus = $this->subscriptionsOldStatus->get($subscriptionId);

        if ($newStatus == $oldStatus) {
            // Probably just a renewal, but there's nothing for us to do
            return;
        }

        if (
            (in_array($oldStatus, ['need-id-check', 'id-was-checked']) || $oldStatus == null)
            && $newStatus == 'active'
        ) {
            if (! Features::accessible(FeatureFlags::USER_MEMBERSHIP_CONTROLS_ACTIVE)) {
                $this->activateMembership();
            }
        }

        if (in_array($newStatus, ['cancelled', 'suspended-payment', 'suspended-manual'])) {
            $anyActive = $this->subscriptionsNewStatus->filter(function ($status) {
                return $status == 'active';
            })->isNotEmpty();

            if (! $anyActive) {
                if (! Features::accessible(FeatureFlags::USER_MEMBERSHIP_CONTROLS_ACTIVE)) {
                    $this->deactivateMembership();
                }
            }
        }

        $this->subscriptionsOldStatus->put($subscriptionId, $newStatus);
    }

    /**
     * When a subscription is imported, we make the assumption that they are already in slack, groups,
     * and the card access system. There won't be any MembershipActivated event because in the real
     * world, that event would have already been emitted.
     *
     * @param SubscriptionImported $event
     */
    protected function applySubscriptionImported(SubscriptionImported $event)
    {
        $this->updateStatus($event->subscription['id'], $event->subscription['status']);
    }

    protected function applySubscriptionCreated(SubscriptionCreated $event)
    {
        $this->updateStatus($event->subscription['id'], $event->subscription['status']);
    }

    protected function applySubscriptionUpdated(SubscriptionUpdated $event)
    {
        $this->updateStatus($event->subscription['id'], $event->subscription['status']);
    }

    protected function updateStatus($subscriptionId, $newStatus)
    {
//        if ($newStatus == 'active') {
//            $this->currentlyAMember = true;
//        }

        $oldStatus = $this->subscriptionsNewStatus->get($subscriptionId);
        $this->subscriptionsOldStatus->put($subscriptionId, $oldStatus);
        $this->subscriptionsNewStatus->put($subscriptionId, $newStatus);
    }
}
