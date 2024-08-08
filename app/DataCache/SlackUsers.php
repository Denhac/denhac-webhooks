<?php

namespace App\DataCache;

use App\External\Slack\SlackApi;

class SlackUsers extends CachedData
{
    public function __construct(
        private readonly SlackApi $slackApi
    )
    {
        parent::__construct();
    }

    public function get() {
        return $this->cache(function () {
            return $this->slackApi->users->list($this->apiProgress('Fetching Slack users'));
        });
    }
}
