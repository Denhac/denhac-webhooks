<?php

namespace App\Aggregates\MembershipTraits;

use App\StorableEvents\GithubUsernameUpdated;

trait Github
{
    public $githubUsername = null;

    private function handleGithub($customer)
    {
        $metadata = collect($customer['meta_data']);
        $githubUsername = $metadata
            ->where('key', 'github_username')
            ->first()['value'] ?? null;

        if ($this->githubUsername != $githubUsername) {
            $this->recordThat(new GithubUsernameUpdated($this->githubUsername, $githubUsername, $this->isActiveMember()));
        }
    }

    public function applyGithubUsernameUpdated(GithubUsernameUpdated $event)
    {
        $this->githubUsername = $event->newUsername;
    }
}
