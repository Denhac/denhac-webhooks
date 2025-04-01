<?php

namespace App\Issues\Types\Slack;

use App\DataCache\MemberData;
use App\Issues\Types\ICanFixThem;
use App\Issues\Types\IssueBase;
use App\Jobs\MakeCustomerRegularMemberInSlack;

class MemberHasRestrictedAccount extends IssueBase
{
    use ICanFixThem;

    private MemberData $member;

    private $slackUser;

    private $limitedType;

    public function __construct(MemberData $member, $slackUser, $limitedType)
    {
        $this->member = $member;
        $this->slackUser = $slackUser;
        $this->limitedType = $limitedType;
    }

    public static function getIssueNumber(): int
    {
        return 302;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return 'Slack: Member has restricted account';
    }

    public function getIssueText(): string
    {
        return "{$this->member->first_name} {$this->member->last_name} with slack id ({$this->slackUser['id']}) is $this->limitedType, but they are a member";
    }

    public function fix(): bool
    {
        return $this->issueFixChoice()
            ->defaultOption('Activate Slack account', function () {
                dispatch(new MakeCustomerRegularMemberInSlack($this->member->id));

                return true;
            })
            ->run();
    }
}
