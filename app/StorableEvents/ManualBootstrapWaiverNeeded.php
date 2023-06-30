<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

/**
 * This event should only be fired from one method in the membership aggregate. It is used to de-activate cards and send
 * an email to all members who have not signed the waiver as a manual bootstrapping step.
 */
class ManualBootstrapWaiverNeeded extends ShouldBeStored
{
    public string $customerId;

    public function __construct($customerId)
    {
        $this->customerId = $customerId;
    }
}
