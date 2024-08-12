<?php

namespace App\Issues\Checkers\WordPress;


use App\DataCache\AggregateCustomerData;
use App\DataCache\WooCommerceUserMemberships;
use App\External\WooCommerce\MetaData;
use App\Issues\Checkers\IssueCheck;
use App\Issues\Checkers\IssueCheckTrait;
use App\Issues\Types\WordPress\ActiveUserMembershipWithNoTeamId;
use App\Issues\Types\WordPress\PausedMembershipWithNoTeamId;
use App\Models\UserMembership;

class TeamSubscriptionIssues implements IssueCheck
{
    use IssueCheckTrait;

    public function __construct(
        private readonly AggregateCustomerData      $aggregateCustomerData,
        private readonly WooCommerceUserMemberships $wooCommerceUserMemberships,
    )
    {
    }

    protected function generateIssues(): void
    {
        $members = $this->aggregateCustomerData->get();
        $fullMemberships = $this->wooCommerceUserMemberships->get()
            ->filter(fn($um) => $um['plan_id'] == UserMembership::MEMBERSHIP_FULL_MEMBER);

        foreach ($fullMemberships as $fullMembership) {
            $member = $members->filter(fn($m) => $m->id == $fullMembership['customer_id'])->first();
            $meta_data = new MetaData($fullMembership['meta_data']);

            $activeStatus = in_array($fullMembership['status'], ['active', 'pending']);
            if (! isset($meta_data['_team_id']) && $activeStatus) {
                // Active or pending cancellation membership is weird with no team associated. Could be old style of
                // how we handled subscriptions for membership status or could be something else. Either way, need to
                // look into it as they might be getting free membership.
                $this->issues->add(new ActiveUserMembershipWithNoTeamId($member, $fullMembership));
            }

            $pausedStatus = $fullMembership['status'] == 'paused';
            if (! isset($meta_data['_team_id']) && $pausedStatus) {
                // on hold membership is weird with no team associated
                $this->issues->add(new PausedMembershipWithNoTeamId($member, $fullMembership));
            }

            // TODO Also check the subscription?
        }
    }
}
