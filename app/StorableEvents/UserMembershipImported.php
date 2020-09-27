<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\ShouldBeStored;

class UserMembershipImported implements ShouldBeStored
{
    public $membership;

    public function __construct($membership)
    {
        $this->membership = $membership;
    }
}
