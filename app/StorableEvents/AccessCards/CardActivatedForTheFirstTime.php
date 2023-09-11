<?php

namespace App\StorableEvents\AccessCards;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

class CardActivatedForTheFirstTime extends ShouldBeStored
{
    public int $customerId;

    public $cardNumber;

    public function __construct($customerId, $cardNumber)
    {
        $this->customerId = $customerId;
        $this->cardNumber = $cardNumber;
    }
}
