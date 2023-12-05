<?php

namespace App\Projectors;

use App\Models\CardUpdateRequest;
use App\StorableEvents\AccessCards\CardActivated;
use App\StorableEvents\AccessCards\CardDeactivated;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;

final class CardUpdateRequestProjector extends Projector
{
    public function onCardActivated(CardActivated $event)
    {
        CardUpdateRequest::where('customer_id', $event->wooCustomerId)
            ->where('card', $event->cardNumber)
            ->where('type', CardUpdateRequest::ACTIVATION_TYPE)
            ->delete();
    }

    public function onCardDeactivated(CardDeactivated $event)
    {
        CardUpdateRequest::where('customer_id', $event->wooCustomerId)
            ->where('card', $event->cardNumber)
            ->where('type', CardUpdateRequest::DEACTIVATION_TYPE)
            ->delete();
    }
}
