<?php

namespace App\Issues\Types\AccessCards;

use App\Issues\Types\IssueBase;

class NonMemberHasActiveCard extends IssueBase
{
    private $cardHolder;

    public function __construct($cardHolder)
    {
        $this->cardHolder = $cardHolder;
    }

    public static function getIssueNumber(): int
    {
        return 3;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return "Access Cards: Non member has active card";
    }

    public function getIssueText(): string
    {
        return "{$this->cardHolder['first_name']} {$this->cardHolder['last_name']} has the active card ({$this->cardHolder['card_num']}) but is not currently a member.";
    }
}
