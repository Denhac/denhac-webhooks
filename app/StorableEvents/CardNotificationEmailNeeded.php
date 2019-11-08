<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\ShouldBeStored;

final class CardNotificationEmailNeeded implements ShouldBeStored
{
    public $cardNotifications;

    public function __construct($cardNotifications)
    {
        $this->cardNotifications = $cardNotifications;
    }
}
