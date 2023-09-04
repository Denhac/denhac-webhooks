<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class PaypalMemberEmailUpdated extends ShouldBeStored
{
    public $paypal_id;

    public $email;

    public function __construct($paypal_id, $email)
    {
        $this->paypal_id = $paypal_id;
        $this->email = $email;
    }
}
