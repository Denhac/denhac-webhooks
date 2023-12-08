<?php

namespace App\Reactors;

use App\Models\CardUpdateRequest;
use App\StorableEvents\AccessCards\CardSentForActivation;
use App\StorableEvents\AccessCards\CardSentForDeactivation;
use Spatie\EventSourcing\EventHandlers\Reactors\Reactor;

final class CardUpdateRequestReactor extends Reactor
{
    public function onCardSentForActivation(CardSentForActivation $event)
    {
        CardUpdateRequest::create([
            'type' => CardUpdateRequest::ACTIVATION_TYPE,
            'customer_id' => $event->wooCustomerId,
            'card' => $event->cardNumber,
        ]);
    }

    public function onCardSentForDeactivation(CardSentForDeactivation $event)
    {
        CardUpdateRequest::create([
            'type' => CardUpdateRequest::DEACTIVATION_TYPE,
            'customer_id' => $event->wooCustomerId,
            'card' => $event->cardNumber,
        ]);
    }
}
