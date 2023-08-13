<?php

namespace App\Issues\Types\InternalConsistency;

use App\Issues\Types\IssueBase;

class UserMembershipDoesNotExistInOurLocalDatabase extends IssueBase
{
    private $userMembershipId;

    public function __construct($userMembershipId)
    {

        $this->userMembershipId = $userMembershipId;
    }
    public static function getIssueNumber(): int
    {
        return 212;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return "Internal Consistency: User membership does not exist in our local database";
    }

    public function getIssueText(): string
    {
        return "User Membership $this->userMembershipId doesn't exist in our local database";
    }
}
