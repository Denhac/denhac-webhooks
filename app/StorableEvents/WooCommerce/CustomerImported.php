<?php

namespace App\StorableEvents\WooCommerce;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class CustomerImported extends ShouldBeStored
{
    public $customer;

    public function __construct($customer)
    {
        $this->customer = $customer;
    }
}
