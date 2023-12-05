<?php

namespace App\Projectors;

use App\Models\Subscription;
use App\StorableEvents\WooCommerce\CustomerDeleted;
use App\StorableEvents\WooCommerce\SubscriptionCreated;
use App\StorableEvents\WooCommerce\SubscriptionDeleted;
use App\StorableEvents\WooCommerce\SubscriptionImported;
use App\StorableEvents\WooCommerce\SubscriptionUpdated;
use Exception;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;

final class SubscriptionProjector extends Projector
{
    public function onStartingEventReplay()
    {
        Subscription::truncate();
    }

    public function onSubscriptionImported(SubscriptionImported $event)
    {
        $this->addOrUpdateSubscriptionFromJson($event->subscription);
    }

    public function onSubscriptionCreated(SubscriptionCreated $event)
    {
        $this->addOrUpdateSubscriptionFromJson($event->subscription);
    }

    public function onSubscriptionUpdated(SubscriptionUpdated $event)
    {
        $this->addOrUpdateSubscriptionFromJson($event->subscription);
    }

    public function onSubscriptionDeleted(SubscriptionDeleted $event)
    {
        /** @var Subscription $subscription */
        $subscription = Subscription::find($event->subscriptionId);

        if (is_null($subscription)) {
            throw new Exception("Failed to find subscription {$event->subscriptionId}");

            return;
        }
        $subscription->delete();
    }

    public function onCustomerDeleted(CustomerDeleted $event)
    {
        Subscription::whereCustomerId($event->customerId)
            ->delete();
    }

    /**
     * @return Subscription
     */
    private function addOrUpdateSubscriptionFromJson($subscription_json)
    {
        $subscriptionModel = Subscription::find($subscription_json['id']);

        if (is_null($subscriptionModel)) {
            /** @var Subscription $subscriptionModel */
            $subscriptionModel = Subscription::make();
        }

        $subscriptionModel->id = $subscription_json['id'];
        $subscriptionModel->status = $subscription_json['status'];
        $subscriptionModel->customer_id = $subscription_json['customer_id'];

        $subscriptionModel->save();

        return $subscriptionModel;
    }
}
