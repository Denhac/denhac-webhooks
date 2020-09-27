<?php

namespace App\Projectors;

use App\StorableEvents\UserMembershipCreated;
use App\UserMembership;
use Spatie\EventSourcing\Projectors\Projector;
use Spatie\EventSourcing\Projectors\ProjectsEvents;

class UserMembershipProjector implements Projector
{
    use ProjectsEvents;

    public function onUserMembershipCreated(UserMembershipCreated $event)
    {
        UserMembership::create([
            'id' => $event->membership['id'],
            'plan_id' => $event->membership['plan_id'],
            'customer_id' => $event->membership['customer_id'],
            'status' => $event->membership['status'],
        ]);
    }
}
