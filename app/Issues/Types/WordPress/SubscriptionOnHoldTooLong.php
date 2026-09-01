<?php

namespace App\Issues\Types\WordPress;

use App\DataCache\MemberData;
use App\Issues\Types\IssueBase;

class SubscriptionOnHoldTooLong extends IssueBase
{
    public function __construct(
        private readonly MemberData $memberData,
        private readonly array $subscription,
    ) {}

    public static function getIssueNumber(): int
    {
        return 605;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return 'Word Press: Subscription on hold longer than expected';
    }

    public function getIssueText(): string
    {
        return "{$this->memberData->full_name} ({$this->memberData->id}) has subscription {$this->subscription['id']} on hold "
            ."since {$this->subscription['date_modified']} with no unpaid renewal order";
    }
}
