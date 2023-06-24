<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class WaiverAccepted extends ShouldBeStored
{
    public array $waiverEvent;

    public function __construct($waiverEvent)
    {
        $this->waiverEvent = $waiverEvent;
    }
}
