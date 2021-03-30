<?php

namespace App\Broadcasting;

use App\User;

class DoorChannel
{
    public function join(User $user): bool
    {
        return $user->tokenCan('door:manage');
    }
}
