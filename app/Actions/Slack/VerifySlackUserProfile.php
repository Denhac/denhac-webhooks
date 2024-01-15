<?php

namespace App\Actions\Slack;

use App\External\Slack\SlackApi;
use App\External\Slack\SlackProfileFields;
use App\External\Slack\SlackRateLimit;
use Spatie\QueueableAction\QueueableAction;

/**
 * This class is needed for 2 reasons.
 *
 * The first is that calling users.profile.set has a limit of 50 in a minute. The RateLimited middleware ensures that
 * only 50 of these can run in the queue at any given time.
 *
 * The second is that fetching the users list does not include custom profile fields and that's exactly what we're
 * wanting to update. So we have to fetch them first (limit is 100 calls / minute) and then we can determine if they
 * even need to be updated.
 */
class VerifySlackUserProfile
{
    use QueueableAction;

    public string $queue = 'slack-rate-limited';

    private SlackApi $slackApi;

    public function __construct(SlackApi $slackApi)
    {
        $this->slackApi = $slackApi;
    }

    public function execute($slackId): void
    {
        $userProfileFields = $this->slackApi->users->profile->get($slackId)['profile']['fields'];

        SlackProfileFields::updateIfNeeded($slackId, $userProfileFields);
    }

    public function middleware()
    {
        return [
            SlackRateLimit::users_profile_get(),
        ];
    }
}
