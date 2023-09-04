<?php

namespace App\Issues\Types\AccessCards;

use App\Issues\Types\IssueBase;

/**
 * This _technically_ should never ever happen. WinDSX should rip the card away from the original card holder on an
 * update. But if it did ever happen, we would absolutely want to know about it.
 */
class ActiveCardMultipleAccounts extends IssueBase
{
    private $cardHolder;

    public function __construct($cardHolder)
    {
        $this->cardHolder = $cardHolder;
    }

    public static function getIssueNumber(): int
    {
        return 1;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return 'Access Cards: Active card multiple accounts';
    }

    public function getIssueText(): string
    {
        return "{$this->cardHolder['first_name']} {$this->cardHolder['last_name']} has the active card ({$this->cardHolder['card_num']}) but is connected to multiple accounts.";
    }
}
