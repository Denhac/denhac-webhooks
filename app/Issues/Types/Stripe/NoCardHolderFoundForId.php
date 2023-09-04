<?php

namespace App\Issues\Types\Stripe;

use App\External\WooCommerce\Api\WooCommerceApi;
use App\Issues\Data\MemberData;
use App\Issues\Types\ICanFixThem;
use App\Issues\Types\IssueBase;

class NoCardHolderFoundForId extends IssueBase
{
    use ICanFixThem;

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
        return 'Stripe: No card holder found for id';
    }

    public function getIssueText(): string
    {
        return "{$this->member->first_name} {$this->member->last_name} has Stripe card holder id {$this->member->stripeCardHolderId} but we have no card holder with that id.";
    }

    public function fix(): bool
    {
        return $this->issueFixChoice()
            ->option('Clear card holder id', fn () => $this->clearCardHolderId())
            ->run();
    }

    private function clearCardHolderId(): bool
    {
        $wooCommerceApi = app(WooCommerceApi::class);

        $wooCommerceApi->customers
            ->update($this->member->id, [
                'meta_data' => [
                    [
                        'key' => 'stripe_card_holder_id',
                        'value' => null,
                    ],
                ],
            ]);

        return true;
    }
}
