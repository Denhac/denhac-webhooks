<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\ShouldBeStored;

final class PaypalMemberDeactivated implements ShouldBeStored
{
    public function __construct()
    {
    }
}
