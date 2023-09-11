<?php

namespace App\StorableEvents\WooCommerce;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class CustomerIsNoEventTestUser extends ShouldBeStored
{
    public $customerId;

    public function __construct($customerId)
    {
        $this->customerId = $customerId;
    }
}
