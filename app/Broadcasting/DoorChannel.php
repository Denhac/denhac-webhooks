<?php

namespace App\Broadcasting;

use App\Models\User;

class DoorChannel
{
    public function join(User $user): bool
    {
        return $user->tokenCan('door:manage');
    }
}
