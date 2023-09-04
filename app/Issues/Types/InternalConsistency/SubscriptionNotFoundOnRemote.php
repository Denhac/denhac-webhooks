<?php

namespace App\Issues\Types\InternalConsistency;

use App\Aggregates\MembershipAggregate;
use App\Issues\Types\ICanFixThem;
use App\Issues\Types\IssueBase;
use App\Models\Subscription;

class SubscriptionNotFoundOnRemote extends IssueBase
{
    use ICanFixThem;

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
        return 'Internal Consistency: Subscription not found on remote';
    }

    public function getIssueText(): string
    {
        return "Subscription $this->subscription_id exists in our local database but not on the website. Deleted?";
    }

    public function fix(): bool
    {
        return $this->issueFixChoice()
            ->option('Delete local subscription', function () {
                /** @var Subscription $subscription */
                $subscription = Subscription::whereWooId($this->subscription_id)->first();

                MembershipAggregate::make($subscription->customer_id)
                    ->deleteSubscription(['id' => $subscription->woo_id])
                    ->persist();

                return true;
            })
            ->run();
    }
}
