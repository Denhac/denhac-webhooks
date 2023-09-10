<?php

namespace App\StorableEvents\GitHub;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class GitHubUsernameUpdated extends ShouldBeStored
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
