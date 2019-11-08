<?php

namespace App\Reactors;

use App\Aggregates\CardNotifierAggregate;
use App\Mail\CardNotification;
use App\StorableEvents\CardActivated;
use App\StorableEvents\CardDeactivated;
use App\StorableEvents\CardNotificationEmailNeeded;
use Illuminate\Support\Facades\Mail;
use Spatie\EventSourcing\EventHandlers\EventHandler;
use Spatie\EventSourcing\EventHandlers\HandlesEvents;

final class CardNotifierReactor implements EventHandler
{
    use HandlesEvents;

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
        Mail::to("jnesselr@denhac.org")->send(new CardNotification($event->cardNotifications));
    }
}
