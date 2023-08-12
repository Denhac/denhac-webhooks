<?php

namespace App\Issues\Types\InternalConsistency;

use App\Issues\Types\IssueBase;

class NonMemberCardIsActivate extends IssueBase
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
        return 208;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return "Internal Consistency: Non member card is activate";
    }

    public function getIssueText(): string
    {
        return "Non-member {$this->member['first_name']} {$this->member['last_name']} has the card {$this->memberCard} but we think it's active when it should not be.";
    }
}
