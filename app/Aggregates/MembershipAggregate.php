<?php

namespace App\Aggregates;

use App\StorableEvents\CardAdded;
use App\StorableEvents\CardRemoved;
use App\StorableEvents\CustomerCreated;
use App\StorableEvents\MemberSubscriptionActivated;
use App\StorableEvents\SubscriptionCreated;
use App\StorableEvents\SubscriptionStatusChanged;
use Ramsey\Uuid\Uuid;
use Spatie\EventSourcing\AggregateRoot;

final class MembershipAggregate extends AggregateRoot
{
    private $customerId;
    private $cards = [];
    private $subscriptionStatus = null;

    /**
     * @param string $customerId
     * @return MembershipAggregate
     */
    public static function retrieve(string $customerId): AggregateRoot
    {
        $uuid = Uuid::uuid5(UUID::NAMESPACE_OID, $customerId);
        $aggregateRoot = AggregateRoot::retrieve($uuid);
        $aggregateRoot->customerId = $customerId;

        return $aggregateRoot;
    }

    public function createCustomer($customer)
    {
        $this->recordThat(new CustomerCreated($customer));

        $this->handleCards($customer);

        return $this;
    }

    public function updateCustomer($customer)
    {
        $this->handleCards($customer);

        return $this;
    }

    public function createSubscription($subscription)
    {
        $this->recordThat(new SubscriptionCreated($subscription));

        $this->handleSubscriptionStatus($subscription["status"]);

        return $this;
    }

    public function updateSubscription($subscription)
    {
        $this->handleSubscriptionStatus($subscription["status"]);

        return $this;
    }

    private function handleCards($customer)
    {
        $metadata = collect($customer["meta_data"]);
        $cardField = $metadata->firstWhere('key', 'access_card_number');

        if($cardField == null) {
            return;
        }

        $cardList = explode(",", $cardField);
        foreach ($cardList as $card) {
            if(!in_array($card, $this->cards)) {
                $this->recordThat(new CardAdded($this->customerId, $card));
            }
        }

        foreach ($this->cards as $card) {
            if(!in_array($card, $cardList)) {
                $this->recordThat(new CardRemoved($this->customerId, $card));
            }
        }
    }

    protected function applyCardAdded(CardAdded $event)
    {
        array_push($this->cards, $event->cardNumber);
    }

    protected function applyCardRemoved(CardRemoved $event)
    {
        $this->cards = array_diff($this->cards, [$event->cardNumber]);
    }

    private function handleSubscriptionStatus($newStatus)
    {
        $oldStatus = $this->subscriptionStatus;
        $this->recordThat(new SubscriptionStatusChanged($oldStatus, $newStatus));

        if($oldStatus == null) {
            $oldStatus = $newStatus;
        }

        // TODO Figure out all the state transitions for subscriptions
        // Also, potentially move this to a reactor
        if($oldStatus == "on-hold" && $newStatus == "active") {
            $this->recordThat(new MemberSubscriptionActivated($this->customerId));
        }
    }

    protected function applySubscriptionStatusChanged(SubscriptionStatusChanged $event)
    {
        $this->subscriptionStatus = $event->newStatus;
    }
}
