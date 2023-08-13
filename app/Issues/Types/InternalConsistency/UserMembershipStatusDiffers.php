<?php

namespace App\Issues\Types\InternalConsistency;

use App\Issues\Types\IssueBase;

class UserMembershipStatusDiffers extends IssueBase
{
    private int $userMembershipId;
    private string $remote_status;
    private string $local_status;

    public function __construct($userMembershipId, $remote_status, $local_status)
    {
        $this->userMembershipId = $userMembershipId;
        $this->remote_status = $remote_status;
        $this->local_status = $local_status;
    }

    public static function getIssueNumber(): int
    {
        return 213;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return "Internal Consistency: User membership status differs";
    }

    public function getIssueText(): string
    {
        return "User Membership $this->userMembershipId has api status $this->remote_status but local status $this->local_status";
    }
}
