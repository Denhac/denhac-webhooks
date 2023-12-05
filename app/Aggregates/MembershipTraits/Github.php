<?php

namespace App\Aggregates\MembershipTraits;

use App\StorableEvents\GitHub\GitHubUsernameUpdated;

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
            $this->recordThat(new GitHubUsernameUpdated($this->githubUsername, $githubUsername, $this->isActiveMember()));
        }
    }

    public function applyGithubUsernameUpdated(GitHubUsernameUpdated $event): void
    {
        $this->githubUsername = $event->newUsername;
    }
}
