<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\ShouldBeStored;

class ADUserToBeDisabled implements ShouldBeStored
{
    private $customerId;

    public function __construct($customerId)
    {
        $this->customerId = $customerId;
    }
}
