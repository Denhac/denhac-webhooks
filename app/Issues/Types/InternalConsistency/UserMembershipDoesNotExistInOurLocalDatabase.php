<?php

namespace App\Issues\Types\InternalConsistency;

use App\Aggregates\MembershipAggregate;
use App\External\WooCommerce\Api\WooCommerceApi;
use App\Issues\Types\ICanFixThem;
use App\Issues\Types\IssueBase;

class UserMembershipDoesNotExistInOurLocalDatabase extends IssueBase
{
    use ICanFixThem;

    private $userMembershipId;

    public function __construct($userMembershipId)
    {

        $this->userMembershipId = $userMembershipId;
    }

    public static function getIssueNumber(): int
    {
        return 212;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return 'Internal Consistency: User membership does not exist in our local database';
    }

    public function getIssueText(): string
    {
        return "User Membership $this->userMembershipId doesn't exist in our local database";
    }

    public function fix(): bool
    {
        return $this->issueFixChoice()
            ->option('Import User Membership from WordPress', function () {
                /** @var WooCommerceApi $wooCommerceApi */
                $wooCommerceApi = app(WooCommerceApi::class);
                $userMembership = $wooCommerceApi->membership->members->get($this->userMembershipId)->toArray();

                MembershipAggregate::make($userMembership['customer_id'])
                    ->importUserMembership($userMembership)
                    ->persist();

                return true;
            })
            ->run();
    }
}
