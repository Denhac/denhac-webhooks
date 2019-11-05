<?php

namespace App\Projectors;

use App\CardUpdateRequest;
use App\StorableEvents\CardActivated;
use App\StorableEvents\CardDeactivated;
use App\StorableEvents\CardSentForActivation;
use App\StorableEvents\CardSentForDeactivation;
use Spatie\EventSourcing\Projectors\Projector;
use Spatie\EventSourcing\Projectors\ProjectsEvents;

final class CardUpdateRequestProjector implements Projector
{
    use ProjectsEvents;

    public function onCardSentForActivation(CardSentForActivation $event)
    {
        CardUpdateRequest::create([
            "type" => CardUpdateRequest::ACTIVATION_TYPE,
            "customer_id" => $event->wooCustomerId,
            "card" => $event->cardNumber,
        ]);
    }

    public function onCardActivated(CardActivated $event)
    {
        CardUpdateRequest::where('customer_id', $event->wooCustomerId)
            ->where('card', $event->cardNumber)
            ->where('type', CardUpdateRequest::ACTIVATION_TYPE)
            ->delete();
    }

    public function onCardSentForDeactivation(CardSentForDeactivation $event)
    {
        CardUpdateRequest::create([
            "type" => CardUpdateRequest::DEACTIVATION_TYPE,
            "customer_id" => $event->wooCustomerId,
            "card" => $event->cardNumber,
        ]);
    }

    public function onCardDeactivated(CardDeactivated $event)
    {
        CardUpdateRequest::where('customer_id', $event->wooCustomerId)
            ->where('card', $event->cardNumber)
            ->where('type', CardUpdateRequest::DEACTIVATION_TYPE)
            ->delete();
    }
}
