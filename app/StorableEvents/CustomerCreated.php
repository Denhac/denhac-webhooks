<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\ShouldBeStored;

final class CustomerCreated implements ShouldBeStored
{
    public $customer;

    public function __construct($customer)
    {
        $this->customer = $customer;
    }
}
