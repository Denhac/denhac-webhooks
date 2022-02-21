<?php

namespace App\Actions\Slack;

use App\Actions\StaticAction;
use App\Slack\SlackApi;
use Illuminate\Support\Facades\Log;
use Spatie\QueueableAction\QueueableAction;

class UpdateSlackUserProfile
{
    use QueueableAction;
    use StaticAction;

    private SlackApi $slackApi;

    public function __construct(SlackApi $slackApi)
    {
        $this->slackApi = $slackApi;
    }

    /**
     * Execute the action.
     *
     * @param string $slackId
     * @param array $fields
     */
    public function execute(string $slackId, array $fields)
    {
        Log::info("Updating fields for {$slackId}: " . print_r($fields, true));
        $response = $this->slackApi->users->profile->set($slackId, [
            'fields' => $fields,
        ]);
        Log::info("Response status: {$response->getStatusCode()}");
        Log::info("Response body: {$response->getBody()->getContents()}");
    }
}
