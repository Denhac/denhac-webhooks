<?php

namespace App\Issues\Types\InternalConsistency;

use App\Aggregates\MembershipAggregate;
use App\External\WooCommerce\Api\WooCommerceApi;
use App\Issues\Types\ICanFixThem;
use App\Issues\Types\IssueBase;

class UserMembershipStatusDiffers extends IssueBase
{
    use ICanFixThem;

    private int $userMembershipId;

    private string $remote_status;

    private string $local_status;

    public function __construct($userMembershipId, $remote_status, $local_status)
    {
        $this->userMembershipId = $userMembershipId;
        $this->remote_status = $remote_status;
        $this->local_status = $local_status;
    }

    public static function getIssueNumber(): int
    {
        return 213;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return 'Internal Consistency: User membership status differs';
    }

    public function getIssueText(): string
    {
        return "User Membership $this->userMembershipId has api status $this->remote_status but local status $this->local_status";
    }

    public function fix(): bool
    {
        return $this->issueFixChoice()
            ->option('Update User Membership from WordPress', function () {
                /** @var WooCommerceApi $wooCommerceApi */
                $wooCommerceApi = app(WooCommerceApi::class);
                $userMembership = $wooCommerceApi->members->get($this->userMembershipId)->toArray();

                MembershipAggregate::make($userMembership['customer_id'])
                    ->updateUserMembership($userMembership)
                    ->persist();

                return true;
            })
            ->run();
    }
}
