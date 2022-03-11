<?php

namespace App\Reactors;

use App\NewMemberCardActivation;
use App\StorableEvents\CardActivated;
use App\StorableEvents\CardSentForActivation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Spatie\EventSourcing\EventHandlers\Reactors\Reactor;

class NewMemberCardReactor extends Reactor implements ShouldQueue
{
    public function onCardSentForActivate(CardSentForActivation $event)
    {
        $newMemberCardActivation = NewMemberCardActivation::search($event->wooCustomerId, $event->cardNumber);

        if(is_null($newMemberCardActivation)) return;

        $newMemberCardActivation->state = NewMemberCardActivation::CARD_SENT_FOR_ACTIVATION;
        $newMemberCardActivation->save();
    }

    public function onCardActivated(CardActivated $event)
    {
        $newMemberCardActivation = NewMemberCardActivation::search($event->wooCustomerId, $event->cardNumber);

        if(is_null($newMemberCardActivation)) return;

        $newMemberCardActivation->state = NewMemberCardActivation::CARD_ACTIVATED;
        $newMemberCardActivation->save();
    }
}
