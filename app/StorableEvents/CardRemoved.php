<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\ShouldBeStored;

final class CardRemoved implements ShouldBeStored
{
    public $wooCustomerId;
    public $cardNumber;

    public function __construct($wooCustomerId, $cardNumber)
    {
        $this->wooCustomerId = $wooCustomerId;
        $this->cardNumber = $cardNumber;
    }
}