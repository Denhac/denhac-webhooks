<?php

namespace App\Issues\Types\InternalConsistency;

use App\DataCache\MemberData;
use App\Issues\Types\IssueBase;

class CannotFindCustomer extends IssueBase
{
    private MemberData $member;

    public function __construct(MemberData $member)
    {
        $this->member = $member;
    }

    public static function getIssueNumber(): int
    {
        return 202;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return 'Internal Consistency: Cannot find customer';
    }

    public function getIssueText(): string
    {
        return "{$this->member->first_name} {$this->member->last_name} with user id {$this->member->id} is not in our database locally";
    }
}
