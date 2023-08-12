<?php

namespace App\Issues\Types\Slack;

use App\Issues\Data\MemberData;
use App\Issues\Types\IssueBase;

class MemberHasRestrictedAccount extends IssueBase
{
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
        return "Slack: Member has restricted account";
    }

    public function getIssueText(): string
    {
        return "{$this->member->first_name} {$this->member->last_name} with slack id ({$this->slackUser['id']}) is $this->limitedType, but they are a member";
    }
}
