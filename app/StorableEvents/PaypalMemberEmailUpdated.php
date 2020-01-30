<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\ShouldBeStored;

final class PaypalMemberEmailUpdated implements ShouldBeStored
{
    public $paypal_id;
    public $email;

    public function __construct($paypal_id, $email)
    {
        $this->paypal_id = $paypal_id;
        $this->email = $email;
    }
}
