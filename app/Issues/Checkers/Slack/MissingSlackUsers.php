<?php

namespace App\Issues\Checkers\Slack;

use App\DataCache\AggregateCustomerData;
use App\DataCache\MemberData;
use App\DataCache\SlackUsers;
use App\Issues\Checkers\IssueCheck;
use App\Issues\Checkers\IssueCheckTrait;
use App\Issues\Checkers\SlackMembershipHelper;
use App\Issues\Types\Slack\MemberDoesNotHaveASlackAccount;

class MissingSlackUsers implements IssueCheck
{
    use IssueCheckTrait;
    use SlackMembershipHelper;

    public function __construct(
        private readonly AggregateCustomerData $aggregateCustomerData,
        private readonly SlackUsers $slackUsers
    ) {}

    public function generateIssues(): void
    {
        $members = $this->aggregateCustomerData->get();
        $slackUsers = $this->slackUsers->get();

        $members
            ->each(function ($member) use ($slackUsers) {
                /** @var MemberData $member */
                if (! $member->isMember) {
                    return;
                }

                $slackForMember = $slackUsers
                    ->filter(function ($user) use ($member) {
                        return $member->slackId == $user['id'];
                    });

                if ($slackForMember->count() == 0) {
                    $this->issues->add(new MemberDoesNotHaveASlackAccount($member));
                }
            });
    }
}
