<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\ShouldBeStored;

final class GithubUsernameUpdated implements ShouldBeStored
{
    public $oldUsername;
    public $newUsername;
    public $isMember;

    public function __construct($oldUsername, $newUsername, $isMember)
    {
        $this->oldUsername = $oldUsername;
        $this->newUsername = $newUsername;
        $this->isMember = $isMember;
    }
}
