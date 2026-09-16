<?php

namespace App\StorableEvents\AccessCards;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

/**
 * @deprecated No longer required, but we still might see it in an event stream. It doesn't harm anything, so we didn't
 * want to incur the cost of cleaning and re-verifying the event stream.
 */
final class CardStatusUpdated extends ShouldBeStored
{
    public function __construct(
        public readonly string $type,
        public readonly int $customer_id,
        public readonly string $card)
    {
    }
}
