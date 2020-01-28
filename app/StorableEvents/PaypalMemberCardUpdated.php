<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\ShouldBeStored;

final class PaypalMemberCardUpdated implements ShouldBeStored
{
    public $paypal_id;
    public $card;

    public function __construct($paypal_id, $card)
    {
        $this->paypal_id = $paypal_id;
        $this->card = $card;
    }
}
