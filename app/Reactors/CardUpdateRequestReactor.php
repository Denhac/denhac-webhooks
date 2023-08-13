<?php

namespace App\Reactors;

use App\CardUpdateRequest;
use App\StorableEvents\CardSentForActivation;
use App\StorableEvents\CardSentForDeactivation;
use Spatie\EventSourcing\EventHandlers\EventHandler;
use Spatie\EventSourcing\EventHandlers\HandlesEvents;

final class CardUpdateRequestReactor implements EventHandler
{
    use HandlesEvents;

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
