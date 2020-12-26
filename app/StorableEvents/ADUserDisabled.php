<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\ShouldBeStored;

class ADUserDisabled implements ShouldBeStored
{
    public $customerId;

    public function __construct($customerId)
    {
        $this->customerId = $customerId;
    }
}
