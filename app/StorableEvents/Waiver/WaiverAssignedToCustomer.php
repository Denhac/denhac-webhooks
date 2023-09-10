<?php

namespace App\StorableEvents\Waiver;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class WaiverAssignedToCustomer extends ShouldBeStored
{
    public string $waiverId;

    public string $customerId;

    public function __construct($waiverId, $customerId)
    {
        $this->waiverId = $waiverId;
        $this->customerId = $customerId;
    }
}
