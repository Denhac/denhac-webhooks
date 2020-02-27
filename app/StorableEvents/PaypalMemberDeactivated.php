<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\ShouldBeStored;

final class PaypalMemberDeactivated implements ShouldBeStored
{
    public $paypal_id;

    public function __construct($paypal_id)
    {
        $this->paypal_id = $paypal_id;
    }
}
