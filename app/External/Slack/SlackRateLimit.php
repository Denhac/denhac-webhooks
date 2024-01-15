<?php

namespace App\External\Slack;

use App\Http\Middleware\SlackPostMessageRateLimit;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\RateLimiter;

/**
 * @method static SlackPostMessageRateLimit chat_postMessage()
 *
 * @method static RateLimited conversations_invite()
 * @method static RateLimited conversations_join()
 * @method static RateLimited conversations_kick()
 * @method static RateLimited conversations_list()
 *
 * @method static RateLimited usergroups_list()
 * @method static RateLimited usergroups_update()
 *
 * @method static RateLimited users_profile_get()
 * @method static RateLimited users_profile_set()
 *
 * @method static RateLimited views_publish()
 */
class SlackRateLimit
{
    protected const POSTING = 'posting';
    protected const TIER_1 = 'tier-1';
    protected const TIER_2 = 'tier-2';
    protected const TIER_3 = 'tier-3';
    protected const TIER_4 = 'tier-4';

    protected static array $tierPerMinute = [
        self::TIER_1 => 1,
        self::TIER_2 => 20,
        self::TIER_3 => 50,
        self::TIER_4 => 100,
    ];

    protected static array $apiCall = [
        'chat_postMessage' => self::POSTING,
        'conversations_invite' => self::TIER_3,
        'conversations_join' => self::TIER_3,
        'conversations_kick' => self::TIER_3,
        'conversations_list' => self::TIER_2,
        'usergroups_list' => self::TIER_2,
        'usergroups_update' => self::TIER_2,
        'users_profile_get' => self::TIER_4,
        'users_profile_set' => self::TIER_3,
        'views_publish' => self::TIER_4,
    ];

    public static function __callStatic(string $name, array $arguments)
    {
        if (!array_key_exists($name, self::$apiCall)) {
            throw new \Exception("Unknown api call $name");
        }

        $tier = self::$apiCall[$name];

        if ($tier === self::POSTING) {
            return app(SlackPostMessageRateLimit::class);
        }

        if (!array_key_exists($tier, self::$tierPerMinute)) {
            throw new \Exception("Unknown tier level $tier for api method $name");
        }

        return self::limit($tier, $name);
    }

    private static function limit($tier, $name)
    {
        if (is_null($name)) {
            $limit_key = "slack-$tier";
        } else {
            $limit_key = "slack-$tier-$name";
        }
        $limiter = RateLimiter::limiter($limit_key);

        if (is_null($limiter)) {
            RateLimiter::for($limit_key, function () use ($tier) {
                return Limit::perMinute(self::$tierPerMinute[$tier]);
            });
        }

        return new RateLimited($limit_key);
    }
}
