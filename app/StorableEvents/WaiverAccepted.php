<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class WaiverAccepted extends ShouldBeStored
{
    public string $waiverEvent;

    public function __construct($waiverEvent)
    {
        $this->waiverEvent = $waiverEvent;
    }
}
