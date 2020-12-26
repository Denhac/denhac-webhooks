<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class ADUserEnabled extends ShouldBeStored
{
    public $customerId;

    public function __construct($customerId)
    {
        $this->customerId = $customerId;
    }
}
