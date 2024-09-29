<?php

namespace App\StorableEvents\AccessCards;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

/**
 * This event includes the card number, but it is not per card. It should only be emitted once per customer. It includes
 * the card so any consumers can more easily grab the card number, even though there should only be one card for that
 * customer. The idea is whomever is doing the ID check can get a notification that a card they submitted was activated.
 * However, if you do it per card you end up having the original ID checker get a notification if the customer changes
 * their card.
 */
class CardActivatedForTheFirstTime extends ShouldBeStored
{
    public int $customerId;

    public $cardNumber;

    public function __construct($customerId, $cardNumber)
    {
        $this->customerId = $customerId;
        $this->cardNumber = $cardNumber;
    }
}
