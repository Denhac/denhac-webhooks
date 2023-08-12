<?php

namespace App\Issues\Types\InternalConsistency;

use App\Issues\Types\IssueBase;

class CardInPossessionOfMultipleCustomers extends IssueBase
{
    private $cardNum;
    private $numEntries;
    private $uniqueCustomers;

    public function __construct($cardNum, $numEntries, $uniqueCustomers)
    {
        $this->cardNum = $cardNum;
        $this->numEntries = $numEntries;
        $this->uniqueCustomers = $uniqueCustomers;
    }

    public static function getIssueNumber(): int
    {
        return 211;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return "Internal Consistency: Card in posession of multiple customers";
    }

    public function getIssueText(): string
    {
        return "Card $this->cardNum has $this->numEntries entries in the database for customer IDs: $this->uniqueCustomers";
    }
}
