<?php

namespace App\StorableEvents\Waiver;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class WaiverAccepted extends ShouldBeStored
{
    public array $waiverEvent;

    public function __construct($waiverEvent)
    {
        $this->waiverEvent = $waiverEvent;
    }
}
