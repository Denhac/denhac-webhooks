<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class PaypalMemberDeactivated extends ShouldBeStored
{
    public $paypal_id;

    public function __construct($paypal_id)
    {
        $this->paypal_id = $paypal_id;
    }
}
