<?php

namespace App\Issues\Checkers\WordPress;

use App\DataCache\AggregateCustomerData;
use App\DataCache\WooCommerceUserMemberships;
use App\External\WooCommerce\MetaData;
use App\Issues\Checkers\IssueCheck;
use App\Issues\Checkers\IssueCheckTrait;
use App\Issues\Types\WordPress\ActiveUserMembershipWithNoSubscription;
use App\Issues\Types\WordPress\ActiveUserMembershipWithNoTeamId;
use App\Issues\Types\WordPress\PausedUserMembershipWithNoSubscription;
use App\Issues\Types\WordPress\PausedUserMembershipWithNoTeamId;
use App\Models\UserMembership;

class TeamSubscriptionIssues implements IssueCheck
{
    use IssueCheckTrait;

    public function __construct(
        private readonly AggregateCustomerData $aggregateCustomerData,
        private readonly WooCommerceUserMemberships $wooCommerceUserMemberships,
    ) {}

    protected function generateIssues(): void
    {
        $members = $this->aggregateCustomerData->get();
        $fullMemberships = $this->wooCommerceUserMemberships->get()
            ->filter(fn ($um) => $um['plan_id'] == UserMembership::MEMBERSHIP_FULL_MEMBER);

        foreach ($fullMemberships as $fullMembership) {
            $member = $members->filter(fn ($m) => $m->id == $fullMembership['customer_id'])->first();
            $meta_data = new MetaData($fullMembership['meta_data']);

            $activeStatus = in_array($fullMembership['status'], ['active', 'pending']);
            $pausedStatus = $fullMembership['status'] == 'paused';

            if (! isset($meta_data['_team_id'])) {
                if($activeStatus) {
                    // Active or pending cancellation membership is weird with no team associated. Could be old style of
                    // how we handled subscriptions for membership status or could be something else. Either way, need to
                    // look into it as they might be getting free membership.
                    $this->issues->add(new ActiveUserMembershipWithNoTeamId($member, $fullMembership));
                }
                if($pausedStatus) {
                    // on hold membership is weird with no team associated as well.
                    $this->issues->add(new PausedUserMembershipWithNoTeamId($member, $fullMembership));
                }
            }

            if (! isset($fullMembership['subscription_id'])) {
                // These statuses can happen for a few reasons, and are only not auto fixable because I don't know all
                // the reasons they can occur just yet. So far, I know if the user has a membership attached to a team,
                // and that team doesn't have a subscription, then it's possible it's because the original owner of the
                // team switched to a regular membership which detached this other person and left them in a team by
                // themselves. Removing them from the team should be sufficient to force the user membership into the
                // correct state.
                if($activeStatus) {
                    $this->issues->add(new ActiveUserMembershipWithNoSubscription($member, $fullMembership));
                }

                if($pausedStatus) {
                    $this->issues->add(new PausedUserMembershipWithNoSubscription($member, $fullMembership));
                }
            }
        }
    }
}
