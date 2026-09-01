<?php

namespace App\Issues\Types\WordPress;

use App\DataCache\MemberData;
use App\External\WooCommerce\Api\WooCommerceApi;
use App\Issues\Types\ICanFixThem;
use App\Issues\Types\IssueBase;

class SubscriptionFirstPaymentFailed extends IssueBase
{
    use ICanFixThem;

    public function __construct(
        private readonly MemberData $memberData,
        private readonly array $subscription,
        private readonly array $parentOrder,
    ) {}

    public static function getIssueNumber(): int
    {
        return 606;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return 'Word Press: Subscription whose first payment never went through';
    }

    public function getIssueText(): string
    {
        return "{$this->memberData->full_name} ({$this->memberData->id}) has subscription {$this->subscription['id']} on hold "
            ."with signup order {$this->parentOrder['id']} \"{$this->parentOrder['status']}\" and unpaid since "
            .$this->parentOrder['date_created'];
    }

    public function fix(): bool
    {
        return $this->issueFixChoice()
            ->option('Cancel the subscription', function () {
                /** @var WooCommerceApi $wooCommerceApi */
                $wooCommerceApi = app(WooCommerceApi::class);

                // They never paid us a cent on this one, so there's nothing to keep it around for. If they turn up
                // later they can sign up again from scratch.
                $wooCommerceApi->subscriptions->update($this->subscription['id'], [
                    'status' => 'cancelled',
                ]);

                return true;
            })
            ->run();
    }
}
