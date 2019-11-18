<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\ShouldBeStored;

final class MembershipDeactivated implements ShouldBeStored
{
    public $customerId;

    public function __construct($customerId)
    {
        $this->customerId = $customerId;
    }
}
