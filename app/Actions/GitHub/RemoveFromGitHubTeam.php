<?php

namespace App\Actions\GitHub;

use App\Actions\StaticAction;
use App\External\GitHub\GitHubApi;
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
        $this->gitHubApi->denhac()->team($team)->remove($username);
    }
}
