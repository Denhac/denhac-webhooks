<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\ShouldBeStored;

final class PaypalMemberNameUpdated implements ShouldBeStored
{
    public $paypal_id;
    public $first_name;
    public $last_name;

    public function __construct($paypal_id, $first_name, $last_name)
    {
        $this->paypal_id = $paypal_id;
        $this->first_name = $first_name;
        $this->last_name = $last_name;
    }
}
