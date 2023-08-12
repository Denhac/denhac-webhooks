<?php

namespace App\Issues\Types\GitHub;

use App\Issues\Data\MemberData;
use App\Issues\Types\IssueBase;

class UsernameDoesNotExist extends IssueBase
{
    private MemberData $member;

    public function __construct(MemberData $member)
    {
        $this->member = $member;
    }

    public static function getIssueNumber(): int
    {
        return 403;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return "Git Hub: Username does not exist";
    }

    public function getIssueText(): string
    {
        return "{$this->member->first_name} {$this->member->last_name} has the GitHub username \"{$this->member->githubUsername}\" which does not exist";
    }
}
