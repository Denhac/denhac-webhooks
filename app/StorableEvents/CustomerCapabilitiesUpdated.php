<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\ShouldBeStored;

class CustomerCapabilitiesUpdated implements ShouldBeStored
{
    public $customerId;
    public $capabilities;

    public function __construct($customerId, $capabilities)
    {
        $this->customerId = $customerId;
        $this->capabilities = $capabilities;
    }
}
