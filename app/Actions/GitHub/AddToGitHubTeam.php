<?php

namespace App\Actions\GitHub;

use App\Actions\StaticAction;
use App\External\GitHub\GitHubApi;
use Spatie\QueueableAction\QueueableAction;

class AddToGitHubTeam
{
    use QueueableAction;
    use StaticAction;

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
