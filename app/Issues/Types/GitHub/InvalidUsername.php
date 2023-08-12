<?php

namespace App\Issues\Types\GitHub;

use App\Issues\Data\MemberData;
use App\Issues\Types\IssueBase;

class InvalidUsername extends IssueBase
{
    private MemberData $member;
    private string $correctedUsername;

    public function __construct(MemberData $member, string $correctedUsername)
    {
        $this->member = $member;
        $this->correctedUsername = $correctedUsername;
    }

    public static function getIssueNumber(): int
    {
        return 402;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return "Git Hub: Invalid git hub username";
    }

    public function getIssueText(): string
    {
        return "{$this->member->first_name} {$this->member->last_name} has the invalid format GitHub username \"{$this->member->githubUsername}\". It may be \"{$this->correctedUsername}\"";
    }
}
