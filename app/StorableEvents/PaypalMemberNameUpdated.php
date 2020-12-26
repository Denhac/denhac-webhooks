<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class PaypalMemberNameUpdated extends ShouldBeStored
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
