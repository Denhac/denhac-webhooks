<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class OctoPrintStatusUpdated extends ShouldBeStored
{
    public array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }
}
