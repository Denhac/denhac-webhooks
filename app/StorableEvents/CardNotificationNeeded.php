<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class CardNotificationNeeded extends ShouldBeStored
{
    public const ACTIVATION_TYPE = 'activation';
    public const DEACTIVATION_TYPE = 'deactivation';
    public $notificationType;
    public $wooCustomerId;
    public $cardNumber;

    public function __construct($notificationType, $wooCustomerId, $cardNumber)
    {
        $this->notificationType = $notificationType;
        $this->wooCustomerId = $wooCustomerId;
        $this->cardNumber = $cardNumber;
    }
}
