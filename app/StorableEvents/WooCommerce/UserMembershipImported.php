<?php

namespace App\StorableEvents\WooCommerce;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class UserMembershipImported extends ShouldBeStored
{
    public $membership;

    public function __construct($membership)
    {
        $this->membership = $membership;
    }
}
