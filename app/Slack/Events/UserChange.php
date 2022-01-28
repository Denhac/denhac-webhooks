<?php

namespace App\Slack\Events;


use App\Actions\Slack\UpdateSlackUserProfile;
use App\Http\Requests\SlackRequest;
use App\Slack\SlackProfileFields;
use Illuminate\Support\Facades\Log;

class UserChange implements EventInterface
{
    public static function eventType(): string
    {
        return 'user_change';
    }

    public function handle(SlackRequest $request)
    {
        $slack_id = $request->getSlackId();
        $profile = $request->event()['user']['profile'];
        $team_id = $request->json('team_id');
        if(array_key_exists("team", $profile) && $profile['team'] != $team_id) {
            return; // This person isn't a member in our slack, probably a connected slack
        }

        $profileFields = $profile['fields'];
        if(is_null($profileFields)) {
            $profileFields = [];
        }
        Log::info("Profile fields: " . print_r($profileFields, true));

        SlackProfileFields::updateIfNeeded($slack_id, $profileFields);
    }
}
