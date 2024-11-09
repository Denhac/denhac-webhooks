<?php

namespace App\Issues\Checkers\Slack;

use App\DataCache\AggregateCustomerData;
use App\DataCache\MemberData;
use App\DataCache\SlackUsers;
use App\Issues\Checkers\IssueCheck;
use App\Issues\Checkers\IssueCheckTrait;
use App\Issues\Checkers\SlackMembershipHelper;
use App\Issues\Types\Slack\FullUserNoRecord;
use App\Issues\Types\Slack\MemberHasRestrictedAccount;
use App\Issues\Types\Slack\NonMemberHasFullAccount;

class ExtraSlackUsers implements IssueCheck
{
    use IssueCheckTrait;
    use SlackMembershipHelper;

    public function __construct(
        private readonly AggregateCustomerData $aggregateCustomerData,
        private readonly SlackUsers $slackUsers
    ) {}

    public function generateIssues(): void
    {
        $slackUsers = $this->slackUsers->get()
            ->filter(function ($user) {
                if (array_key_exists('is_bot', $user) && $user['is_bot']) {
                    return false;
                }

                if (
                    $user['id'] == 'UNEA0SKK3' || // slack-api
                    $user['id'] == 'USLACKBOT' // slackbot
                ) {
                    return false;
                }

                return true;
            });

        $members = $this->aggregateCustomerData->get();

        $slackUsers
            ->each(function ($user) use ($members) {
                $membersForSlackId = $members
                    ->filter(function ($member) use ($user) {
                        /** @var MemberData $member */
                        return $member->slackId == $user['id'];
                    });

                if ($membersForSlackId->count() == 0) {  // TODO Check for multi/single channel guests outside of public as well
                    if ($this->isFullSlackUser($user)) {
                        $this->issues->add(new FullUserNoRecord($user));
                    }

                    return;
                }

                /** @var MemberData $member */
                $member = $membersForSlackId->first();

                if ($member->isMember) {
                    if (array_key_exists('is_invited_user', $user) && $user['is_invited_user']) {
                        return; // Do nothing, we've sent the invite and that's all we can do.
                    } elseif (array_key_exists('deleted', $user) && $user['deleted']) {
                        $this->issues->add(new MemberHasRestrictedAccount($member, $user, 'deleted'));
                    } elseif (array_key_exists('is_restricted', $user) && $user['is_restricted']) {
                        $this->issues->add(new MemberHasRestrictedAccount($member, $user, 'a multi-channel guest'));
                    } elseif (array_key_exists('is_ultra_restricted', $user) && $user['is_ultra_restricted']) {
                        $this->issues->add(new MemberHasRestrictedAccount($member, $user, 'a single-channel guest'));

                    }
                } elseif ($this->isFullSlackUser($user)) {  // TODO Check for multi/single channel guests outside of public as well
                    $this->issues->add(new NonMemberHasFullAccount($member, $user));
                }
            });
    }
}
