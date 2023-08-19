<?php

namespace App\Issues\Types\AccessCards;

use App\Issues\Data\MemberData;
use App\Issues\Types\IssueBase;

class UnknownActiveCardForMember extends IssueBase
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
        return 6;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return "Access Cards: Unknown active card for member";
    }

    public function getIssueText(): string
    {
        return "{$this->member->first_name} {$this->member->last_name} might have the active access card ({$this->cardNumber}) but it's not in their profile";
    }
}
