<?php

namespace App\Actions\Slack;

use App\Actions\StaticAction;
use App\External\Slack\SlackApi;
use App\External\Slack\SlackRateLimit;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Spatie\QueueableAction\QueueableAction;

class UpdateSlackUserProfile
{
    private const LAST_UPDATE_TIME_CACHE_KEY_PREFIX = 'UpdateSlackUserProfile_Last_Update_';

    use QueueableAction;
    use StaticAction;

    public string $queue = 'slack-rate-limited';

    private SlackApi $slackApi;

    public function __construct(SlackApi $slackApi)
    {
        $this->slackApi = $slackApi;
    }

    /**
     * Execute the action.
     */
    public function execute(string $slackId, array $fields)
    {
        $updateTimeCacheKey = self::LAST_UPDATE_TIME_CACHE_KEY_PREFIX . $slackId;
        if (Cache::has($updateTimeCacheKey)) {
            $lastUpdateTime = Cache::get($updateTimeCacheKey);
            if ($lastUpdateTime >= Carbon::now()->subMinute()) {
                return; // Prevent constantly updating for unknown reason.
            }
        }
        Cache::forever($updateTimeCacheKey, Carbon::now());

        Log::info("Updating fields for {$slackId}: " . print_r($fields, true));
        $response = $this->slackApi->users->profile->set($slackId, [
            'fields' => $fields,
        ]);
        Log::info("Response status: {$response->getStatusCode()}");
        Log::info("Response body: {$response->getBody()->getContents()}");
    }

    public function middleware()
    {
        return [
            SlackRateLimit::users_profile_set(),
        ];
    }
}
