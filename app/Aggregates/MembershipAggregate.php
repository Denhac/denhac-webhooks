<?php

namespace App\Aggregates;

use App\StorableEvents\CardActivated;
use App\StorableEvents\CardAdded;
use App\StorableEvents\CardDeactivated;
use App\StorableEvents\CardRemoved;
use App\StorableEvents\CardSentForActivation;
use App\StorableEvents\CardSentForDeactivation;
use App\StorableEvents\CustomerCreated;
use App\StorableEvents\MemberSubscriptionActivated;
use App\StorableEvents\SubscriptionCreated;
use App\StorableEvents\SubscriptionStatusChanged;
use Ramsey\Uuid\Uuid;
use Spatie\EventSourcing\AggregateRoot;

final class MembershipAggregate extends AggregateRoot
{
    private $customerId;

    private $cardsOnAccount;
    private $cardsNeedingActivation; // They need activation only if this person is a confirmed member
    private $cardsSentForActivation;
    private $cardsSentForDeactivation;

    private $subscriptionStatus = null;

    public function __construct()
    {
        $this->cardsOnAccount = collect();
        $this->cardsNeedingActivation = collect();
        $this->cardsSentForActivation = collect();
        $this->activatedCards = collect();
        $this->cardsSentForDeactivation = collect();
        $this->deactivatedCards = collect();
    }

    /**
     * @param string $customerId
     * @return MembershipAggregate
     */
    public static function make(string $customerId): AggregateRoot
    {
        $uuid = Uuid::uuid5(UUID::NAMESPACE_OID, $customerId);
        $aggregateRoot = MembershipAggregate::retrieve($uuid);
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

        $this->handleSubscriptionStatus($subscription["id"], $subscription["status"]);

        return $this;
    }

    public function updateSubscription($subscription)
    {
        $this->handleSubscriptionStatus($subscription["id"], $subscription["status"]);

        return $this;
    }

    private function handleCards($customer)
    {
        $metadata = collect($customer["meta_data"]);
        $cardMetadata = $metadata->firstWhere('key', 'access_card_number');

        if($cardMetadata == null) {
            return;
        }

        $cardField = $cardMetadata["value"];

        $cardList = collect(explode(",", $cardField));
        foreach ($cardList as $card) {
            if(!$this->cardsOnAccount->contains($card)) {
                $this->recordThat(new CardAdded($this->customerId, $card));

                if($this->isActiveMember()) {
                    $this->recordThat(new CardSentForActivation($this->customerId, $card));
                }
            }
        }

        foreach ($this->cardsOnAccount as $card) {
            if(!$cardList->contains($card)) {
                $this->recordThat(new CardRemoved($this->customerId, $card));
                $this->recordThat(new CardSentForDeactivation($this->customerId, $card));
            }
        }
    }

    protected function applyCardAdded(CardAdded $event)
    {
        $this->cardsOnAccount->push($event->cardNumber);
        $this->cardsNeedingActivation->push($event->cardNumber);
    }

    protected function applyCardSentForActivation(CardSentForActivation $event)
    {
        $this->cardsNeedingActivation->pull($event->cardNumber);
        $this->cardsSentForActivation->push($event->cardNumber);
    }

    protected function applyCardActivated(CardActivated $event)
    {
        $this->cardsSentForActivation->pull($event->cardNumber);
    }

    protected function applyCardRemoved(CardRemoved $event)
    {
        $this->cardsOnAccount->push($event->cardNumber);
    }

    protected function applyCardSentForDeactivation(CardSentForDeactivation $event)
    {
        $this->cardsSentForDeactivation->push($event->cardNumber);
    }

    protected function applyCardDeactivated(CardDeactivated $event)
    {
        $this->cardsSentForDeactivation->pull($event->cardNumber);
    }

    private function handleSubscriptionStatus($subscriptionId, $newStatus)
    {
        $oldStatus = $this->subscriptionStatus;
        $this->recordThat(new SubscriptionStatusChanged($subscriptionId, $oldStatus, $newStatus));

        if($oldStatus == null) {
            $oldStatus = $newStatus;
        }

        // TODO Figure out all the state transitions for subscriptions
        if($oldStatus == "on-hold" && $newStatus == "active") {
            $this->recordThat(new MemberSubscriptionActivated($this->customerId));

            foreach ($this->cardsNeedingActivation as $card) {
                $this->recordThat(new CardSentForActivation($this->customerId, $card));
            }
        }
    }

    protected function applySubscriptionStatusChanged(SubscriptionStatusChanged $event)
    {
        $this->subscriptionStatus = $event->newStatus;
    }

    private function isActiveMember()
    {
        return $this->subscriptionStatus == "active";
    }
}
