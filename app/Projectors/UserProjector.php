<?php

namespace App\Projectors;

use App\StorableEvents\UserCreated;
use App\User;
use Illuminate\Support\Str;
use Spatie\EventSourcing\Projectors\Projector;
use Spatie\EventSourcing\Projectors\ProjectsEvents;

final class UserProjector implements Projector
{
    use ProjectsEvents;

    public function onUserCreated(UserCreated $event)
    {
        User::create([
            'name' => $event->name,
            'api_token' => Str::random(80),
        ]);
    }
}
