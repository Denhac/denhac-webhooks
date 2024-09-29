<?php

namespace App\Issues\Types\AccessCards;

use App\DataCache\MemberData;
use App\Issues\Types\IssueBase;

class CustomerHasBothCardAndTemporaryCode extends IssueBase
{
    public function __construct(
        private readonly MemberData $member
    )
    {
    }

    public static function getIssueNumber(): int
    {
        return 7;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return "Access Cards: Customer has both card and temporary code";
    }

    public function getIssueText(): string
    {
        return "{$this->member->full_name} has both an access card and a temporary code";
    }
}
