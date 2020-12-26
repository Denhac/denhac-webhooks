<?php

namespace App\Aggregates\MembershipTraits;

use App\CardUpdateRequest;
use App\StorableEvents\CardActivated;
use App\StorableEvents\CardAdded;
use App\StorableEvents\CardDeactivated;
use App\StorableEvents\CardRemoved;
use App\StorableEvents\CardSentForActivation;
use App\StorableEvents\CardSentForDeactivation;
use App\StorableEvents\CardStatusUpdated;
use Exception;

trait Cards
{
    public $cardsOnAccount;
    public $cardsNeedingActivation; // They need activation only if this person is a confirmed member
    public $cardsSentForActivation;
    public $cardsSentForDeactivation;

    public function bootCards()
    {
        $this->cardsOnAccount = collect();
        $this->cardsNeedingActivation = collect();
        $this->cardsSentForActivation = collect();
        $this->cardsSentForDeactivation = collect();
    }

    public function updateCardStatus(CardUpdateRequest $cardUpdateRequest, $status)
    {
        if (! $this->respondToEvents) {
            return $this;
        }

        $this->recordThat(new CardStatusUpdated(
            $cardUpdateRequest->type,
            $cardUpdateRequest->customer_id,
            $cardUpdateRequest->card
        ));

        if ($status == CardUpdateRequest::STATUS_SUCCESS) {
            if ($cardUpdateRequest->type == CardUpdateRequest::ACTIVATION_TYPE) {
                $this->recordThat(new CardActivated($this->customerId, $cardUpdateRequest->card));
            } else if ($cardUpdateRequest->type == CardUpdateRequest::DEACTIVATION_TYPE) {
                $this->recordThat(new CardDeactivated($this->customerId, $cardUpdateRequest->card));
            } else {
                $message = "Card update request type wasn't one of the expected values: {$cardUpdateRequest->type}";
                throw new Exception($message);
            }
        } else {
            $message = "Card update (Customer: $cardUpdateRequest->customer_id, "
                ."Card: $cardUpdateRequest->card, Type: $cardUpdateRequest->type) "
                .'not successful';
            throw new Exception($message);
        }

        return $this;
    }

    private function handleCards($customer)
    {
        $metadata = collect($customer['meta_data']);
        $cardMetadata = $metadata->firstWhere('key', 'access_card_number');

        if ($cardMetadata == null) {
            return;
        }

        $cardField = $cardMetadata['value'];

        $cardList = collect(explode(',', $cardField));
        foreach ($cardList as $card) {
            if (is_null($card) || $card === '') {
                continue;
            }

            if (! $this->cardsOnAccount->contains($card)) {
                $this->recordThat(new CardAdded($this->customerId, $card));

                if ($this->isActiveMember()) {
                    $this->recordThat(new CardSentForActivation($this->customerId, $card));
                }
            }
        }

        foreach ($this->allCards() as $card) {
            if (! $cardList->contains($card)) {
                $this->recordThat(new CardRemoved($this->customerId, $card));
                $this->recordThat(new CardSentForDeactivation($this->customerId, $card));
            }
        }
    }

    public function activateCardsNeedingActivation()
    {
        foreach ($this->allCards() as $card) {
            $this->recordThat(new CardSentForActivation($this->customerId, $card));
        }
    }

    public function deactivateAllCards()
    {
        foreach ($this->allCards() as $card) {
            $this->recordThat(new CardSentForDeactivation($this->customerId, $card));
        }
    }

    protected function applyCardAdded(CardAdded $event)
    {
        $this->cardsOnAccount->push($event->cardNumber);
        $this->cardsNeedingActivation->push($event->cardNumber);
    }

    protected function applyCardSentForActivation(CardSentForActivation $event)
    {
        $this->cardsNeedingActivation = $this->cardsNeedingActivation
            ->reject(function ($value) use ($event) {
                return $value == $event->cardNumber;
            });
        $this->cardsSentForActivation->push($event->cardNumber);
    }

    protected function applyCardActivated(CardActivated $event)
    {
        $this->cardsSentForActivation = $this->cardsSentForActivation
            ->reject(function ($value) use ($event) {
                return $value == $event->cardNumber;
            });
    }

    protected function applyCardRemoved(CardRemoved $event)
    {
        $this->cardsOnAccount = $this->cardsOnAccount
            ->reject(function ($value) use ($event) {
                return $value == $event->cardNumber;
            });
    }

    protected function applyCardSentForDeactivation(CardSentForDeactivation $event)
    {
        $this->cardsSentForDeactivation->push($event->cardNumber);
    }

    protected function applyCardDeactivated(CardDeactivated $event)
    {
        $this->cardsSentForDeactivation = $this->cardsSentForDeactivation
            ->reject(function ($value) use ($event) {
                return $value == $event->cardNumber;
            });
    }

    private function allCards()
    {
        return collect()
            ->merge($this->cardsNeedingActivation)
            ->merge($this->cardsSentForActivation)
            ->merge($this->cardsOnAccount)
            ->unique();
    }
}
