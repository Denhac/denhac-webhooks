<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\ShouldBeStored;

final class CardNotificationNeeded implements ShouldBeStored
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
