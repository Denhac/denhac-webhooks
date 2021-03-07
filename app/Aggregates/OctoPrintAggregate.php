<?php

namespace App\Aggregates;

use App\StorableEvents\OctoPrintStatusUpdated;
use Spatie\EventSourcing\AggregateRoots\AggregateRoot;

class OctoPrintAggregate extends AggregateRoot
{
    private const GLOBAL_UUID = 'cb4e02ad-a286-44cc-83cb-2f9df0b7ab98';

    /**
     * @return OctoPrintAggregate
     */
    public static function make(): AggregateRoot
    {
        // We only have one instance of an OctoPrint Aggregate
        return self::retrieve(self::GLOBAL_UUID);
    }

    public function handle($payload)
    {
        $this->recordThat(new OctoPrintStatusUpdated($payload));
    }
}
