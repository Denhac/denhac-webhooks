<?php

namespace App\Issues\Checkers;


use App\Issues\IssueData;
use App\Issues\Types\Slack\FullUserNoRecord;
use App\Issues\Types\Slack\MemberHasRestrictedAccount;
use App\Issues\Types\Slack\NonMemberHasFullAccount;

class ExtraSlackUsers implements IssueCheck
{
    use IssueCheckTrait;
    use SlackMembershipHelper;

    private IssueData $issueData;

    public function __construct(IssueData $issueData)
    {
        $this->issueData = $issueData;
    }

    public function generateIssues(): void
    {
        $slackUsers = $this->issueData->slackUsers()
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

        $members = $this->issueData->members();

        $slackUsers
            ->each(function ($user) use ($members) {
                $membersForSlackId = $members
                    ->filter(function ($member) use ($user) {
                        return $member['slack_id'] == $user['id'];
                    });

                if ($membersForSlackId->count() == 0) {
                    if ($this->isFullSlackUser($user)) {
                        $this->issues->add(new FullUserNoRecord($user));
                    }

                    return;
                }

                $member = $membersForSlackId->first();

                if ($member['is_member']) {
                    if (array_key_exists('is_invited_user', $user) && $user['is_invited_user']) {
                        return; // Do nothing, we've sent the invite and that's all we can do.
                    } else if (array_key_exists('deleted', $user) && $user['deleted']) {
                        $this->issues->add(new MemberHasRestrictedAccount($member, $user, "deleted"));
                    } else if (array_key_exists('is_restricted', $user) && $user['is_restricted']) {
                        $this->issues->add(new MemberHasRestrictedAccount($member, $user, "a multi-channel guest"));
                    } else if (array_key_exists('is_ultra_restricted', $user) && $user['is_ultra_restricted']) {
                        $this->issues->add(new MemberHasRestrictedAccount($member, $user, "a single-channel guest"));

                    }
                } elseif ($this->isFullSlackUser($user)) {
                    $this->issues->add(new NonMemberHasFullAccount($member, $user));
                }
            });
    }
}
