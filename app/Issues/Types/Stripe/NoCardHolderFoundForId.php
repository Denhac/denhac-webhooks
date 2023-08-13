<?php

namespace App\Issues\Types\Stripe;

use App\Issues\Data\MemberData;
use App\Issues\Types\IssueBase;

class NoCardHolderFoundForId extends IssueBase
{
    private MemberData $member;

    public function __construct(MemberData $member)
    {
        $this->member = $member;
    }

    public static function getIssueNumber(): int
    {
        return 502;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return "Stripe: No card holder found for id";
    }

    public function getIssueText(): string
    {
        return "{$this->member->first_name} {$this->member->last_name} has Stripe card holder id {$this->member->stripeCardHolderId} but we have no card holder with that id.";
    }
}
