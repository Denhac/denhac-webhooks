<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\ShouldBeStored;

final class PaypalMemberSlackIdUpdated implements ShouldBeStored
{
    public $paypal_id;
    public $slack_id;

    public function __construct($paypal_id, $slack_id)
    {
        $this->paypal_id = $paypal_id;
        $this->slack_id = $slack_id;
    }
}
