<?php

namespace App\Issues\Types\Slack;

use App\Issues\Types\IssueBase;

class MemberDoesNotHaveASlackAccount extends IssueBase
{
    private $member;

    public function __construct($member)
    {
        $this->member = $member;
    }

    public static function getIssueNumber(): int
    {
        return 300;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return "Slack: Member does not have a slack account";
    }

    public function getIssueText(): string
    {
        return "{$this->member['first_name']} {$this->member['last_name']} ({$this->member['id']}) doesn't appear to have a slack account";
    }
}
