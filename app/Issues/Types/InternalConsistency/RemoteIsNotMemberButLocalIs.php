<?php

namespace App\Issues\Types\InternalConsistency;

use App\Issues\Types\IssueBase;

class RemoteIsNotMemberButLocalIs extends IssueBase
{
    private $member;

    public function __construct($member)
    {
        $this->member = $member;
    }

    public static function getIssueNumber(): int
    {
        return 200;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return "Internal Consistency: Remote is not member but local is";
    }

    public function getIssueText(): string
    {
        return "{$this->member['first_name']} {$this->member['last_name']} appears to be active in our system but not in WooCommerce";
    }
}
