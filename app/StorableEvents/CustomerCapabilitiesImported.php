<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class CustomerCapabilitiesImported extends ShouldBeStored
{
    public $customerId;
    public $capabilities;

    public function __construct($customerId, $capabilities)
    {
        $this->customerId = $customerId;
        $this->capabilities = $capabilities;
    }
}
