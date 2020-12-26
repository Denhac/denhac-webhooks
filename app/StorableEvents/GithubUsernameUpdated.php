<?php

namespace App\StorableEvents;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class GithubUsernameUpdated extends ShouldBeStored
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
