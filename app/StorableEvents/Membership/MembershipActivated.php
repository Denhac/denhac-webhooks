<?php

namespace App\StorableEvents\Membership;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class MembershipActivated extends ShouldBeStored
{
    public $customerId;

    public function __construct($customerId)
    {
        $this->customerId = $customerId;
    }
}
