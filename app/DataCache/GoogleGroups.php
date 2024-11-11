<?php

namespace App\DataCache;

use App\External\Google\GoogleApi;

class GoogleGroups extends CachedData
{
    public function __construct(
        private readonly GoogleApi $googleApi
    ) {
        parent::__construct();
    }

    public function get()
    {
        return $this->cache(function () {
            return $this->googleApi->groupsForDomain('denhac.org');
        });
    }
}
