<?php

namespace App\Issues\Types\AccessCards;

use App\Issues\Data\MemberData;
use App\Issues\Types\IssueBase;

class MemberCardIsNotActive extends IssueBase
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
        return 4;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return "Access Cards: Member card is not active";
    }

    public function getIssueText(): string
    {
        return "{$this->member->first_name} {$this->member->last_name} has the card $this->cardNumber but it doesn't appear to be active";
    }
}
