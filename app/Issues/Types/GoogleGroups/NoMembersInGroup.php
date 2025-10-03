<?php

namespace App\Issues\Types\GoogleGroups;

use App\Issues\Types\IssueBase;

class NoMembersInGroup extends IssueBase
{
    public function __construct(
        private readonly string $group
    )
    {
    }

    public static function getIssueNumber(): int
    {
        return 105;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return "Google Groups: No members in group";
    }

    public function getIssueText(): string
    {
        return "No emails are in the group $this->group";
    }
}
