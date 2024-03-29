<?php

namespace App\Reactors;

use App\Aggregates\CardNotifierAggregate;
use App\Mail\CardNotificationEmail;
use App\StorableEvents\AccessCards\CardActivated;
use App\StorableEvents\AccessCards\CardDeactivated;
use App\StorableEvents\AccessCards\CardNotificationEmailNeeded;
use Illuminate\Support\Facades\Mail;
use Spatie\EventSourcing\EventHandlers\Reactors\Reactor;

final class CardNotifierReactor extends Reactor
{
    public function onCardActivated(CardActivated $event)
    {
        CardNotifierAggregate::make()
            ->notifyOfCardActivation($event)
            ->persist();
    }

    public function onCardDeactivated(CardDeactivated $event)
    {
        CardNotifierAggregate::make()
            ->notifyOfCardDeactivation($event)
            ->persist();
    }

    public function onCardNotificationEmailNeeded(CardNotificationEmailNeeded $event)
    {
        Mail::send(new CardNotificationEmail($event->cardNotifications));
    }
}
