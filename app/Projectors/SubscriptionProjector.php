<?php

namespace App\Projectors;

use App\StorableEvents\WooCommerce\CustomerDeleted;
use App\StorableEvents\WooCommerce\SubscriptionCreated;
use App\StorableEvents\WooCommerce\SubscriptionDeleted;
use App\StorableEvents\WooCommerce\SubscriptionImported;
use App\StorableEvents\WooCommerce\SubscriptionUpdated;
use App\Models\Subscription;
use Exception;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;
use Spatie\EventSourcing\EventHandlers\Projectors\ProjectsEvents;

final class SubscriptionProjector extends Projector
{
    use ProjectsEvents;

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
        $subscription = Subscription::find($event->subscription);

        if (is_null($subscription)) {
            report(new Exception("Failed to find subscription {$event->subscription}"));

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
        $subscriptionModel = Subscription::whereWooId($subscription_json['id'])->first();

        if (is_null($subscriptionModel)) {
            /** @var Subscription $subscriptionModel */
            $subscriptionModel = Subscription::make();
        }

        $subscriptionModel->id = $subscription_json['id'];
        $subscriptionModel->woo_id = $subscription_json['id'];
        $subscriptionModel->status = $subscription_json['status'];
        $subscriptionModel->customer_id = $subscription_json['customer_id'];

        $subscriptionModel->save();

        return $subscriptionModel;
    }
}
