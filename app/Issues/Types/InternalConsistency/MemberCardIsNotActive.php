<?php

namespace App\Issues\Types\InternalConsistency;

use App\Issues\Types\IssueBase;

class MemberCardIsNotActive extends IssueBase
{
    private $member;
    private $memberCard;

    public function __construct($member, $memberCard)
    {
        $this->member = $member;
        $this->memberCard = $memberCard;
    }

    public static function getIssueNumber(): int
    {
        return 207;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return "Internal Consistency: Member card is not active";
    }

    public function getIssueText(): string
    {
        return "Member {$this->member['first_name']} {$this->member['last_name']} has the card {$this->memberCard} but we think it's NOT active";
    }
}
