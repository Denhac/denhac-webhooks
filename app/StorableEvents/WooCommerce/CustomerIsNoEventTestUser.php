<?php

namespace App\StorableEvents\WooCommerce;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class CustomerIsNoEventTestUser extends ShouldBeStored
{
    public $woo_customer_id;

    public function __construct($woo_customer_id)
    {
        $this->woo_customer_id = $woo_customer_id;
    }
}
