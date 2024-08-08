<?php

namespace App\DataCache;

use App\External\GitHub\GitHubApi;

class GitHubPendingMembers extends CachedData
{
    public function __construct(
        private readonly GitHubApi $gitHubApi
    )
    {
        parent::__construct();
    }

    public function get() {
        return $this->cache(function () {
            return $this->gitHubApi->denhac()
                ->pendingInvitations($this->apiProgress("Fetching invites of 'denhac' GitHub organization"));
        });
    }
}
