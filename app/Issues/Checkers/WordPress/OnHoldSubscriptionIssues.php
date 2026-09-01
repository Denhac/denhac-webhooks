<?php

namespace App\Issues\Checkers\WordPress;

use App\DataCache\AggregateCustomerData;
use App\DataCache\MemberData;
use App\DataCache\WooCommerceSubscriptions;
use App\DataCache\WooCommerceUnpaidSubscriptionOrders;
use App\Issues\Checkers\IssueCheck;
use App\Issues\Checkers\IssueCheckTrait;
use App\Issues\Types\WordPress\SubscriptionFirstPaymentFailed;
use App\Issues\Types\WordPress\SubscriptionOnHoldTooLong;
use App\Issues\Types\WordPress\SubscriptionStuckOnHoldAfterFailedRenewal;
use App\Models\UserMembership;
use Carbon\Carbon;

class OnHoldSubscriptionIssues implements IssueCheck
{
    use IssueCheckTrait;

    /**
     * When a renewal payment fails, WooCommerce puts the subscription on hold and works through its retry rules over
     * the next few days. The last of those rules is the one that cancels the subscription, so anything inside that
     * window is still in progress and none of our business.
     */
    private const int GRACE_PERIOD_DAYS = 7;

    public function __construct(
        private readonly AggregateCustomerData $aggregateCustomerData,
        private readonly WooCommerceSubscriptions $wooCommerceSubscriptions,
        private readonly WooCommerceUnpaidSubscriptionOrders $unpaidSubscriptionOrders,
    ) {}

    protected function generateIssues(): void
    {
        $members = $this->aggregateCustomerData->get()->keyBy(fn ($m) => $m->id);
        $cutoff = Carbon::now()->subDays(self::GRACE_PERIOD_DAYS);

        $onHoldSubscriptions = $this->wooCommerceSubscriptions->get()
            ->filter(fn ($subscription) => $subscription['status'] == 'on-hold');

        foreach ($onHoldSubscriptions as $subscription) {
            $member = $members->get($subscription['customer_id']);

            if (is_null($member) || $this->waitingOnAnIdCheck($member, $subscription)) {
                continue;
            }

            $unpaidOrder = $this->unpaidSubscriptionOrders->forSubscription($subscription['id']);

            if (! is_null($unpaidOrder)) {
                if (Carbon::parse($unpaidOrder['date_created'])->lessThan($cutoff)) {
                    // A subscription only ever has the one parent order, the one they placed at signup. If that is what
                    // went unpaid then they never actually started paying us and there was never a renewal to stall.
                    if ($unpaidOrder['id'] == $subscription['parent_id']) {
                        $this->issues->add(new SubscriptionFirstPaymentFailed($member, $subscription, $unpaidOrder));
                    } else {
                        $this->issues->add(new SubscriptionStuckOnHoldAfterFailedRenewal($member, $subscription, $unpaidOrder));
                    }
                }

                continue;
            }

            if (Carbon::parse($subscription['date_modified'])->lessThan($cutoff)) {
                $this->issues->add(new SubscriptionOnHoldTooLong($member, $subscription));
            }
        }
    }

    /**
     * A new full membership is put on hold until the member comes in and gets their ID checked. That's on purpose, and
     * it stays that way until the check happens, so there's nothing to report and no reason to spend an API call
     * looking at their orders.
     *
     * Only the subscription behind the membership itself waits on that. Someone can skip the ID check entirely and
     * still be donating every month, and a donation of theirs stuck on hold is worth hearing about.
     */
    private function waitingOnAnIdCheck(MemberData $member, array $subscription): bool
    {
        if ($member->idChecked) {
            return false;
        }

        return $member->userMemberships->contains(
            fn ($userMembership) => $userMembership['plan_id'] == UserMembership::MEMBERSHIP_FULL_MEMBER
                && isset($userMembership['subscription_id'])
                && $userMembership['subscription_id'] == $subscription['id']
        );
    }
}
