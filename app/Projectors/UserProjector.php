<?php

namespace App\Projectors;

use App\StorableEvents\UserCreated;
use App\User;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;
use Spatie\EventSourcing\EventHandlers\Projectors\ProjectsEvents;

final class UserProjector extends Projector
{
    use ProjectsEvents;

    public function onStartingEventReplay()
    {
        User::truncate();
    }

    public function onUserCreated(UserCreated $event)
    {
        User::create([
            'name' => $event->name,
            'api_token' => $event->api_token,
        ]);
    }
}
