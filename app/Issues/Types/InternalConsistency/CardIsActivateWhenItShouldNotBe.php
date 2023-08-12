<?php

namespace App\Issues\Types\InternalConsistency;

use App\Issues\Data\MemberData;
use App\Issues\Types\IssueBase;

class CardIsActivateWhenItShouldNotBe extends IssueBase
{
    private MemberData $member;
    private $memberCard;

    public function __construct(MemberData $member, $memberCard)
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
        return "{$this->member->first_name} {$this->member->last_name} has the card {$this->memberCard} and we think it's active when it should not be.";
    }
}
