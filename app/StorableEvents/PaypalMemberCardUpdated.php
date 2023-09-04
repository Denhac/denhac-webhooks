<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class PaypalMemberCardUpdated extends ShouldBeStored
{
    public $paypal_id;

    public $card;

    public function __construct($paypal_id, $card)
    {
        $this->paypal_id = $paypal_id;
        $this->card = $card;
    }
}
