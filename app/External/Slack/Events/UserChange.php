<?php

namespace App\External\Slack\Events;

use App\External\Slack\SlackProfileFields;
use App\Http\Requests\SlackRequest;

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
        if (array_key_exists('team', $profile) && $profile['team'] != $team_id) {
            return; // This person isn't a member in our slack, probably a connected slack
        }

        $profileFields = $profile['fields'];
        if (is_null($profileFields)) {
            $profileFields = [];
        }

        SlackProfileFields::updateIfNeeded($slack_id, $profileFields);
    }
}
