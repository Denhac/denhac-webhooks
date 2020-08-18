<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\ShouldBeStored;

class CustomerIsNoEventTestUser implements ShouldBeStored
{
    public $woo_customer_id;

    public function __construct($woo_customer_id)
    {
        $this->woo_customer_id = $woo_customer_id;
    }
}
