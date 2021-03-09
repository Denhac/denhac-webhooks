<?php

namespace App\Slack\Events;


use App\Actions\UpdateSlackUserProfileMembership;
use App\Http\Requests\SlackRequest;
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
        $profileFields = $request->event()['user']['profile']['fields'];
        if(is_null($profileFields)) {
            $profileFields = [];
        }
        Log::info("Profile fields: " . print_r($profileFields, true));

        $key = setting(UpdateSlackUserProfileMembership::MEMBERSHIP_FIELD_SETTING_KEY);
        if (is_null($key)) {
            return;
        }

        if (!array_key_exists($key, $profileFields)) {
            Log::info("{$key} is not in profile fields");
            self::updateMembershipField($slack_id);
        } else {
            $membershipValue = $profileFields[$key]['value'];
            $customer = $request->customer();

            if (is_null($customer)) {
                return;
            }

            if ($customer->member && $membershipValue == 'No') {
                Log::info("{$customer->username} is a member, but membership value is No");
                self::updateMembershipField($slack_id);
            } else if (!$customer->member && $membershipValue == 'Yes') {
                Log::info("{$customer->username} is not a member, but membership value is Yes");
                self::updateMembershipField($slack_id);
            }
        }
    }

    /**
     * @param $id
     */
    protected static function updateMembershipField($id): void
    {
        /** @var UpdateSlackUserProfileMembership $action */
        $action = app(UpdateSlackUserProfileMembership::class);
        $action
            ->onQueue()
            ->execute($id);
    }
}
