<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Queue\InteractsWithQueue;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

/**
 * The normal rate limiting is on a per minute scale. Slack's post message limits are 1 per second. The cache can handle
 * it but the API is somewhat limited. This class is based on \Illuminate\Queue\Middleware\RateLimited but we can
 * simplify it a bit since we aren't trying to limit it by a specific name or handle multiple rate limits. We also
 * assume there's no such thing as too many retries for posting a message. The queue will eventually clear or hit our
 * max failures for the queue.
 */
class SlackPostMessageRateLimit
{
    private static string $cacheKey = "slack-post-message-rate-limit";
    protected Cache $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param InteractsWithQueue $job
     * @throws InvalidArgumentException
     */
    public function handle(mixed $job, Closure $next): Response
    {
        if($this->cache->has(self::$cacheKey)) {
            $job->release(1);  // Try again in one second
        }

        $added = $this->cache->add(self::$cacheKey, 0, 1);
        if(! $added) {
            // Concurrent job must have added it and we were second. Relies on cache to handle race condition
            $job->release(1);
        }

        return $next($job);
    }

    /**
     * After deserialization, we need our cache again.
     *
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function __wakeup()
    {
        $this->cache = Container::getInstance()->make(Cache::class);
    }
}
