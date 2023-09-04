<?php

namespace App\Issues\Types\InternalConsistency;

use App\Issues\Types\IssueBase;

class SubscriptionDoesNotExistInOurLocalDatabase extends IssueBase
{
    private int $subscription_id;

    public function __construct($subscription_id)
    {
        $this->subscription_id = $subscription_id;
    }

    public static function getIssueNumber(): int
    {
        return 203;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return 'Internal Consistency: Subscription does not exist in our local database';
    }

    public function getIssueText(): string
    {
        return "Subscription $this->subscription_id doesn't exist in our local database";
    }
}
