<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\ShouldBeStored;

final class MembershipActivated implements ShouldBeStored
{
    public $customerId;

    public function __construct($customerId)
    {
        $this->customerId = $customerId;
    }
}
