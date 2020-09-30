<?php

namespace App\Projectors;

use App\StorableEvents\CustomerDeleted;
use App\StorableEvents\SubscriptionCreated;
use App\StorableEvents\SubscriptionImported;
use App\StorableEvents\SubscriptionUpdated;
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

    public function onSubscriptionUpdated(SubscriptionUpdated $event)
    {
        $subscription = $this->addOrGetSubscription($event->subscription);

        $subscription->status = $event->subscription['status'];

        $subscription->save();
    }

    public function onCustomerDeleted(CustomerDeleted $event)
    {
        Subscription::whereCustomerId($event->customerId)
            ->delete();
    }

    /**
     * @param $subscription
     * @return Subscription
     */
    private function addOrGetSubscription($subscription)
    {
        $subscriptionModel = Subscription::whereWooId($subscription['id'])->first();

        if (is_null($subscriptionModel)) {
            $wooId = $subscription['id'];
            $customerId = $subscription['customer_id'];
            $status = $subscription['status'];

            return Subscription::create([
                'woo_id' => $wooId,
                'customer_id' => $customerId,
                'status' => $status,
            ]);
        } else {
            return $subscriptionModel;
        }
    }
}
