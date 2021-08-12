<?php

namespace App\Actions\GitHub;

use App\Actions\StaticAction;
use App\GitHub\GitHubApi;
use Spatie\QueueableAction\QueueableAction;

class RemoveFromGitHubTeam
{
    use QueueableAction;
    use StaticAction;

    private GitHubApi $gitHubApi;

    public function __construct(GitHubApi $gitHubApi)
    {
        $this->gitHubApi = $gitHubApi;
    }

    public function execute($username, $team)
    {
        $this->gitHubApi->team($team)->remove($username);
    }
}
