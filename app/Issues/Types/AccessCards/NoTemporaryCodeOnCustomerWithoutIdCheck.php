<?php

namespace App\Issues\Types\AccessCards;

use App\DataCache\MemberData;
use App\Issues\Types\IssueBase;

class NoTemporaryCodeOnCustomerWithoutIdCheck extends IssueBase
{
    public function __construct(
        private readonly MemberData $member
    )
    {
    }

    public static function getIssueNumber(): int
    {
        return 8;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return "Access Cards: No temporary code on customer without id check";
    }

    public function getIssueText(): string
    {
        return "{$this->member->full_name} has no temporary code to get their access card and has not had their id checked.";
    }
}
