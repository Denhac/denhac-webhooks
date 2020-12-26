<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class CustomerCreated extends ShouldBeStored
{
    public $customer;

    public function __construct($customer)
    {
        $this->customer = $customer;
    }
}
