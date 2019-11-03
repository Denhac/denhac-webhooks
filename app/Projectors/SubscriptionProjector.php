<?php

namespace App\Projectors;

use App\StorableEvents\SubscriptionCreated;
use App\StorableEvents\SubscriptionStatusChanged;
use App\Subscription;
use Spatie\EventSourcing\Projectors\Projector;
use Spatie\EventSourcing\Projectors\ProjectsEvents;

final class SubscriptionProjector implements Projector
{
    use ProjectsEvents;

    public function onSubscriptionCreated(SubscriptionCreated $event)
    {
        $subscription = $event->subscription;

        $wooId = $subscription["id"];
        $customerId = $subscription["customer_id"];
        $status = $subscription["status"];

        Subscription::create([
            "woo_id" => $wooId,
            "customer_id" => $customerId,
            "status" => $status,
        ]);
    }

    public function onSubscriptionStatusChanged(SubscriptionStatusChanged $event)
    {

        /** @var Subscription $subscription */
        $subscription = Subscription::whereWooId($event->subscriptionId)->first();

        $subscription->status = $event->newStatus;

        $subscription->save();
    }
}
