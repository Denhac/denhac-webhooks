<?php

namespace App\Issues\Types\AccessCards;

use App\Issues\Data\MemberData;
use App\Issues\Types\IssueBase;

class CardHolderIncorrectName extends IssueBase
{
    private MemberData $member;
    private $cardHolder;

    public function __construct(MemberData $member, $cardHolder)
    {
        $this->cardHolder = $cardHolder;
        $this->member = $member;
    }

    public static function getIssueNumber(): int
    {
        return 2;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return "Access Cards: Card holder incorrect name";
    }

    public function getIssueText(): string
    {
        return "{$this->cardHolder['first_name']} {$this->cardHolder['last_name']} has the active access card ({$this->cardHolder['card_num']}) but is listed as {$this->member->first_name} {$this->member->last_name} in WordPress.";
    }
}
