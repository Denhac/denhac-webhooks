<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class IdWasChecked extends ShouldBeStored
{
    public int $customerId;

    public function __construct($customerId)
    {
        $this->customerId = $customerId;
    }
}
