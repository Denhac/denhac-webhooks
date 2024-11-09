<?php

namespace App\DataCache;

use App\External\GitHub\GitHubApi;

class GitHubFailedInvites extends CachedData
{
    public function __construct(
        private readonly GitHubApi $gitHubApi
    ) {
        parent::__construct();
    }

    public function get()
    {
        return $this->cache(function () {
            return $this->gitHubApi->denhac()
                ->failedInvitations($this->apiProgress("Fetching failed invites of 'denhac' GitHub organization"));
        });
    }
}
