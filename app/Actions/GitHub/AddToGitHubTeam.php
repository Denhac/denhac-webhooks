<?php

namespace App\Actions\GitHub;

use App\GitHub\GitHubApi;
use Spatie\QueueableAction\QueueableAction;

class AddToGitHubTeam
{
    use QueueableAction;

    private GitHubApi $githubApi;

    public function __construct(GitHubApi $githubApi)
    {
        $this->githubApi = $githubApi;
    }

    public function execute($username, $team)
    {
        $this->githubApi->team($team)->add($username);
    }
}
