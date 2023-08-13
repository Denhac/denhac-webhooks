<?php

namespace App\Issues\Types\InternalConsistency;

use App\Issues\Types\IssueBase;

class UserMembershipNotFoundOnRemote extends IssueBase
{
    private int $userMembershipId;

    public function __construct($userMembershipId)
    {
        $this->userMembershipId = $userMembershipId;
    }

    public static function getIssueNumber(): int
    {
        return 214;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return "Internal Consistency: User membership not found on remote";
    }

    public function getIssueText(): string
    {
        return "User Membership $this->userMembershipId exists in our local database but not on the website. Deleted?";
    }
}
