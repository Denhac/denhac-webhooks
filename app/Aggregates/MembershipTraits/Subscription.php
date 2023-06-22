<?php

namespace App\Aggregates\MembershipTraits;

use App\FeatureFlags;
use App\StorableEvents\SubscriptionCreated;
use App\StorableEvents\SubscriptionImported;
use App\StorableEvents\SubscriptionUpdated;
use Illuminate\Support\Collection;
use YlsIdeas\FeatureFlags\Facades\Features;

/**
 * We don't actually use subscription status changes to do anything anymore, but we keep them here just in case that is
 * useful in the future.
 */
trait Subscription
{
    public Collection $subscriptionsOldStatus;
    public Collection $subscriptionsNewStatus;

    public function bootSubscription(): void
    {
        $this->subscriptionsOldStatus = collect();
        $this->subscriptionsNewStatus = collect();
    }

    public function handleSubscriptionStatus($subscriptionId, $newStatus): void
    {
        $oldStatus = $this->subscriptionsOldStatus->get($subscriptionId);

        if ($newStatus == $oldStatus) {
            // Probably just a renewal, there's nothing for us to do
            return;
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
        $oldStatus = $this->subscriptionsNewStatus->get($subscriptionId);
        $this->subscriptionsOldStatus->put($subscriptionId, $oldStatus);
        $this->subscriptionsNewStatus->put($subscriptionId, $newStatus);
    }
}
