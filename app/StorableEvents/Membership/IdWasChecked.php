<?php

namespace App\StorableEvents\Membership;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class IdWasChecked extends ShouldBeStored
{
    public int $customerId;

    public function __construct($customerId)
    {
        $this->customerId = $customerId;
    }
}
