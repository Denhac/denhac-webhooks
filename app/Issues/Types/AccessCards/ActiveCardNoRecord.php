<?php

namespace App\Issues\Types\AccessCards;

use App\Issues\Types\IssueBase;

class ActiveCardNoRecord extends IssueBase
{
    private $cardHolder;

    public function __construct($cardHolder)
    {
        $this->cardHolder = $cardHolder;
    }

    public static function getIssueNumber(): int
    {
        return 5;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return 'Access Cards: Active card no record';
    }

    public function getIssueText(): string
    {
        return "{$this->cardHolder['first_name']} {$this->cardHolder['last_name']} has the active card ({$this->cardHolder['card_num']}) but I have no membership record of them with that card.";
    }
}
