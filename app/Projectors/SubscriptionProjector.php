<?php

namespace App\Projectors;

use App\StorableEvents\SubscriptionCreated;
use App\StorableEvents\SubscriptionImported;
use App\StorableEvents\SubscriptionStatusChanged;
use App\Subscription;
use Spatie\EventSourcing\Projectors\Projector;
use Spatie\EventSourcing\Projectors\ProjectsEvents;

final class SubscriptionProjector implements Projector
{
    use ProjectsEvents;

    public function onStartingEventReplay()
    {
        Subscription::truncate();
    }

    public function onSubscriptionImported(SubscriptionImported $event)
    {
        $this->addOrGetSubscription($event->subscription);
    }

    public function onSubscriptionCreated(SubscriptionCreated $event)
    {
        $this->addOrGetSubscription($event->subscription);
    }

    public function onSubscriptionStatusChanged(SubscriptionStatusChanged $event)
    {
        /** @var Subscription $subscription */
        $subscription = Subscription::whereWooId($event->subscriptionId)->first();

        if($subscription == null) {
            return;
        }

        $subscription->status = $event->newStatus;

        $subscription->save();
    }

    /**
     * @param $subscription
     */
    private function addOrGetSubscription($subscription): void
    {
        $wooId = $subscription['id'];
        $customerId = $subscription['customer_id'];
        $status = $subscription['status'];

        Subscription::create([
            'woo_id' => $wooId,
            'customer_id' => $customerId,
            'status' => $status,
        ]);
    }
}
