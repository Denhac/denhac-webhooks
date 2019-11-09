<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\ShouldBeStored;

final class CardAdded implements ShouldBeStored
{
    public $wooCustomerId;
    public $cardNumber;

    public function __construct($wooCustomerId, $cardNumber)
    {
        $this->wooCustomerId = $wooCustomerId;
        $this->cardNumber = $cardNumber;
    }
}