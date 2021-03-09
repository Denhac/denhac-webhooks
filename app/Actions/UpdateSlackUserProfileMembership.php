<?php

namespace App\Actions;

use App\Customer;
use App\Slack\SlackApi;
use Spatie\QueueableAction\QueueableAction;

class UpdateSlackUserProfileMembership
{
    const MEMBERSHIP_FIELD_SETTING_KEY = 'slack.fields.membership';

    use QueueableAction;

    private SlackApi $slackApi;

    public function __construct(SlackApi $slackApi)
    {
        $this->slackApi = $slackApi;
    }

    /**
     * Execute the action.
     *
     * @param string $slackId
     */
    public function execute(string $slackId)
    {
        $key = setting(UpdateSlackUserProfileMembership::MEMBERSHIP_FIELD_SETTING_KEY);
        if(is_null($key)) {
            return;
        }

        /** @var Customer $customer */
        $customer = Customer::whereSlackId($slackId)->first();
        $memberValue = "Yes";
        if(is_null($customer) || !$customer->member) {
            $memberValue = "No";
        }

        $this->slackApi->user_profile_set($slackId, [
            'fields' => [
                $key => [
                    'name' => $memberValue,
                ],
            ],
        ]);
    }
}
