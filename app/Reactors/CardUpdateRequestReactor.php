<?php

namespace App\Reactors;

use App\Jobs\IssueCardUpdateRequest;
use App\StorableEvents\CardSentForActivation;
use App\StorableEvents\CardSentForDeactivation;
use Spatie\EventSourcing\EventHandlers\EventHandler;
use Spatie\EventSourcing\EventHandlers\HandlesEvents;

final class CardUpdateRequestReactor implements EventHandler
{
    use HandlesEvents;

    public function onCardSentForActivation(CardSentForActivation $event)
    {
        dispatch(new IssueCardUpdateRequest($event));
    }

    public function onCardSentForDeactivation(CardSentForDeactivation $event)
    {
        dispatch(new IssueCardUpdateRequest($event));
    }
}
