<?php

namespace App\Issues\Types\InternalConsistency;

use App\Issues\Data\MemberData;
use App\Issues\Types\IssueBase;

class CustomerHasUnknownCard extends IssueBase
{
    private MemberData $member;

    private $cardNumber;

    public function __construct(MemberData $member, $cardNumber)
    {
        $this->member = $member;
        $this->cardNumber = $cardNumber;
    }

    public static function getIssueNumber(): int
    {
        return 206;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return 'Internal Consistency: Customer has unknown card';
    }

    public function getIssueText(): string
    {
        return "{$this->member->first_name} {$this->member->last_name} has the card {$this->cardNumber} in WordPress but it's not listed in our database";
    }
}
