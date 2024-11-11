<?php

namespace App\DataCache;

use App\External\GitHub\GitHubApi;

class GitHubMembers extends CachedData
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
                ->listMembers($this->apiProgress("Fetching members of 'denhac' GitHub organization"));
        });
    }
}
