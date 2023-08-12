<?php

namespace App\Issues\Types\InternalConsistency;

use App\Issues\Data\MemberData;
use App\Issues\Types\IssueBase;

class ActiveCardNotInCustomerProfile extends IssueBase
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
        return 209;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return "Internal Consistency: Active card not in customer profile";
    }

    public function getIssueText(): string
    {
        return "{$this->member->first_name} {$this->member->last_name} doesn't have {$this->cardNumber} " .
            "listed in their profile, but we think it's active";
    }
}
