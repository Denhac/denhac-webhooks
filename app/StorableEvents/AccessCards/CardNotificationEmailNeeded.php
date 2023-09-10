<?php

namespace App\StorableEvents\AccessCards;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class CardNotificationEmailNeeded extends ShouldBeStored
{
    public $cardNotifications;

    public function __construct($cardNotifications)
    {
        $this->cardNotifications = $cardNotifications;
    }
}
