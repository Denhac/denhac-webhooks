<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\ShouldBeStored;

class ADUserToBeEnabled implements ShouldBeStored
{
    private $customerId;

    public function __construct($customerId)
    {
        $this->customerId = $customerId;
    }
}
