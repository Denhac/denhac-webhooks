<?php

namespace App\Reactors;

use App\ADUpdateRequest;
use App\StorableEvents\ADUserToBeDisabled;
use App\StorableEvents\ADUserToBeEnabled;
use Spatie\EventSourcing\EventHandlers\EventHandler;
use Spatie\EventSourcing\EventHandlers\HandlesEvents;

class ADUpdateRequestReactor implements EventHandler
{
    use HandlesEvents;

    public function onADUserToBeEnabled(ADUserToBeEnabled $event)
    {
        ADUpdateRequest::create([
            'type' => ADUpdateRequest::ACTIVATION_TYPE,
            'customer_id' => $event->customerId,
        ]);
    }

    public function onADUserToBeDisabled(ADUserToBeDisabled $event)
    {
        ADUpdateRequest::create([
            'type' => ADUpdateRequest::DEACTIVATION_TYPE,
            'customer_id' => $event->customerId,
        ]);
    }
}
