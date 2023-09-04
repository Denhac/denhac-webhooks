<?php

namespace App\Projectors;

use App\Models\CardUpdateRequest;
use App\StorableEvents\CardActivated;
use App\StorableEvents\CardDeactivated;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;
use Spatie\EventSourcing\EventHandlers\Projectors\ProjectsEvents;

final class CardUpdateRequestProjector extends Projector
{
    use ProjectsEvents;

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
