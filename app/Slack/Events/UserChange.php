<?php

namespace App\Slack\Events;


use App\Actions\UpdateSlackUserProfileMembership;
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
        $profileFields = $request->json('event')['user']['profile']['fields'];

        $key = setting(UpdateSlackUserProfileMembership::MEMBERSHIP_FIELD_SETTING_KEY);
        if(is_null($key)) {
            return;
        }

        if(! in_array($key, $profileFields)) {
            self::updateMembershipField($slack_id);
        } else {
            $membershipValue = $profileFields[$key]['value'];
            $customer = $request->customer();

            if (is_null($customer)) {
                self::updateMembershipField($slack_id);
            } else if ($customer->member && $membershipValue == 'No') {
                self::updateMembershipField($slack_id);
            } else if (!$customer->member && $membershipValue == 'Yes') {
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
