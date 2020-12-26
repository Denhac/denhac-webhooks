<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class CardStatusUpdated extends ShouldBeStored
{
    /**
     * @var string
     */
    public $type;
    /**
     * @var int
     */
    public $customer_id;
    /**
     * @var string
     */
    public $card;

    public function __construct(string $type, int $customer_id, string $card)
    {
        $this->type = $type;
        $this->customer_id = $customer_id;
        $this->card = $card;
    }
}
