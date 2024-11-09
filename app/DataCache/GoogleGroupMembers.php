<?php

namespace App\DataCache;

use App\External\Google\GoogleApi;

class GoogleGroupMembers extends CachedData
{
    public function __construct(
        private readonly GoogleApi $googleApi
    ) {
        parent::__construct();
    }

    public function get($groupName)
    {
        return $this->cache($groupName, function () use ($groupName) {
            return $this->googleApi->group($groupName)
                ->list($this->apiProgress("Fetching Google Group Members $groupName"));
        });
    }
}
