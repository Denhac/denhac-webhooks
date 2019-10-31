<?php

namespace App\Projectors;

use App\StorableEvents\SubscriptionUpdated;
use App\Subscription;
use Spatie\EventSourcing\Projectors\Projector;
use Spatie\EventSourcing\Projectors\ProjectsEvents;

final class SubscriptionProjector implements Projector
{
    use ProjectsEvents;

    public function onSubscriptionUpdated(SubscriptionUpdated $event)
    {
        Subscription::create([
            "woo_id" => $event->wooId,
            "customer_id" => $event->customerId,
            "status" => $event->status,
        ]);
    }
}
