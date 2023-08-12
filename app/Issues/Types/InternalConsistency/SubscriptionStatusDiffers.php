<?php

namespace App\Issues\Types\InternalConsistency;

use App\Issues\Types\IssueBase;

class SubscriptionStatusDiffers extends IssueBase
{
    private int $subscription_id;
    private string $remote_status;
    private string $local_status;

    public function __construct($subscription_id, $remote_status, $local_status)
    {
        $this->subscription_id = $subscription_id;
        $this->remote_status = $remote_status;
        $this->local_status = $local_status;
    }

    public static function getIssueNumber(): int
    {
        return 204;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return "Internal Consistency: Subscription status differs";
    }

    public function getIssueText(): string
    {
        return "Subscription $this->subscription_id has api status $this->remote_status but local status $this->local_status";
    }
}
