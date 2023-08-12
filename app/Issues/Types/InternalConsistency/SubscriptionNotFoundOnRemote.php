<?php

namespace App\Issues\Types\InternalConsistency;

use App\Issues\Types\IssueBase;

class SubscriptionNotFoundOnRemote extends IssueBase
{
    private int $subscription_id;

    public function __construct($subscription_id)
    {
        $this->subscription_id = $subscription_id;
    }

    public static function getIssueNumber(): int
    {
        return 205;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return "Internal Consistency: Subscription not found on remote";
    }

    public function getIssueText(): string
    {
        return "Subscription $this->subscription_id exists in our local database but not on the website. Deleted?";
    }
}
