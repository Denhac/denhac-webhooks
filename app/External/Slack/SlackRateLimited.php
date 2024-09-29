<?php

namespace App\External\Slack;

use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Queue\Middleware\RateLimited;

class SlackRateLimited extends RateLimited
{
    public function hit(): void
    {
        /** @var \Illuminate\Cache\RateLimiter $limiter */
        $limiter = app()->make(RateLimiter::class);
        /** @var Limit $limit */
        $limit = $limiter->limiter($this->limiterName)();
        $decayMinutes = $limit->decayMinutes;
        $limiter->hit($this->limiterName, $decayMinutes);
    }
}
