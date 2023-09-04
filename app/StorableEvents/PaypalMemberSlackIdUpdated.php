<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class PaypalMemberSlackIdUpdated extends ShouldBeStored
{
    public $paypal_id;

    public $slack_id;

    public function __construct($paypal_id, $slack_id)
    {
        $this->paypal_id = $paypal_id;
        $this->slack_id = $slack_id;
    }
}
