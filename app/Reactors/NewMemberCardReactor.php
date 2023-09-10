<?php

namespace App\Reactors;

use App\Models\NewMemberCardActivation;
use App\StorableEvents\AccessCards\CardSentForActivation;
use App\StorableEvents\AccessCards\CardActivated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Spatie\EventSourcing\EventHandlers\Reactors\Reactor;

class NewMemberCardReactor extends Reactor implements ShouldQueue
{
    public function onCardSentForActivate(CardSentForActivation $event)
    {
        $newMemberCardActivation = NewMemberCardActivation::search($event->wooCustomerId, $event->cardNumber);

        if (is_null($newMemberCardActivation)) {
            return;
        }

        $newMemberCardActivation->state = NewMemberCardActivation::CARD_SENT_FOR_ACTIVATION;
        $newMemberCardActivation->save();
    }

    public function onCardActivated(CardActivated $event)
    {
        $newMemberCardActivation = NewMemberCardActivation::search($event->wooCustomerId, $event->cardNumber);

        if (is_null($newMemberCardActivation)) {
            return;
        }

        $newMemberCardActivation->state = NewMemberCardActivation::CARD_ACTIVATED;
        $newMemberCardActivation->save();
    }
}
