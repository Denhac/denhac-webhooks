<?php

namespace App\Issues\Types\WordPress;

use App\DataCache\MemberData;
use App\External\WooCommerce\Api\WooCommerceApi;
use App\Issues\Types\ICanFixThem;
use App\Issues\Types\IssueBase;

class SubscriptionStuckOnHoldAfterFailedRenewal extends IssueBase
{
    use ICanFixThem;

    public function __construct(
        private readonly MemberData $memberData,
        private readonly array $subscription,
        private readonly array $renewalOrder,
    ) {}

    public static function getIssueNumber(): int
    {
        return 604;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return 'Word Press: Subscription stuck on hold after a failed renewal';
    }

    public function getIssueText(): string
    {
        return "{$this->memberData->full_name} ({$this->memberData->id}) has subscription {$this->subscription['id']} on hold "
            ."with renewal order {$this->renewalOrder['id']} \"{$this->renewalOrder['status']}\" and unpaid since "
            .$this->renewalOrder['date_created'];
    }

    public function fix(): bool
    {
        return $this->issueFixChoice()
            ->option('Cancel the subscription', function () {
                /** @var WooCommerceApi $wooCommerceApi */
                $wooCommerceApi = app(WooCommerceApi::class);

                $wooCommerceApi->subscriptions->update($this->subscription['id'], [
                    'status' => 'cancelled',
                ]);

                return true;
            })
            ->run();
    }
}
