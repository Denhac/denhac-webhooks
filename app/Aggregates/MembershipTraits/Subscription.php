<?php

namespace App\Aggregates\MembershipTraits;

use App\StorableEvents\WooCommerce\SubscriptionCreated;
use App\StorableEvents\WooCommerce\SubscriptionImported;
use App\StorableEvents\WooCommerce\SubscriptionUpdated;
use Illuminate\Support\Collection;

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
     */
    protected function applySubscriptionImported(SubscriptionImported $event): void
    {
        $this->updateStatus($event->subscription['id'], $event->subscription['status']);
    }

    protected function applySubscriptionCreated(SubscriptionCreated $event): void
    {
        $this->updateStatus($event->subscription['id'], $event->subscription['status']);
    }

    protected function applySubscriptionUpdated(SubscriptionUpdated $event): void
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
