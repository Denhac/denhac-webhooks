<?php

namespace App\StorableEvents\Membership;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class MembershipDeactivated extends ShouldBeStored
{
    public $customerId;

    public function __construct($customerId)
    {
        $this->customerId = $customerId;
    }
}
