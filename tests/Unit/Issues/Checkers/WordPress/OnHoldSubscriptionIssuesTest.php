<?php

namespace Tests\Unit\Issues\Checkers\WordPress;

use App\DataCache\AggregateCustomerData;
use App\DataCache\WooCommerceCustomers;
use App\DataCache\WooCommerceSubscriptions;
use App\DataCache\WooCommerceUnpaidSubscriptionOrders;
use App\DataCache\WooCommerceUserMemberships;
use App\Issues\Checkers\WordPress\OnHoldSubscriptionIssues;
use App\Issues\Types\WordPress\SubscriptionFirstPaymentFailed;
use App\Issues\Types\WordPress\SubscriptionOnHoldTooLong;
use App\Issues\Types\WordPress\SubscriptionStuckOnHoldAfterFailedRenewal;
use App\Models\UserMembership;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OnHoldSubscriptionIssuesTest extends TestCase
{
    private const string LONG_AGO = '2026-01-01T00:00:00';

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-08-31T00:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function failed_renewal_order_past_the_grace_period_is_stuck(): void
    {
        $issues = $this->issuesFor(
            $this->idCheckedCustomer(),
            $this->onHoldSubscription(),
            $this->renewalOrder('failed', self::LONG_AGO),
        );

        self::assertCount(1, $issues);
        self::assertInstanceOf(SubscriptionStuckOnHoldAfterFailedRenewal::class, $issues->first());
    }

    /**
     * Where the retry rules stop determines whether the order is left failed or pending. Both mean the same thing.
     */
    #[Test]
    public function pending_renewal_order_past_the_grace_period_is_stuck(): void
    {
        $issues = $this->issuesFor(
            $this->idCheckedCustomer(),
            $this->onHoldSubscription(),
            $this->renewalOrder('pending', self::LONG_AGO),
        );

        self::assertCount(1, $issues);
        self::assertInstanceOf(SubscriptionStuckOnHoldAfterFailedRenewal::class, $issues->first());
    }

    #[Test]
    public function unpaid_renewal_order_inside_the_grace_period_is_left_alone(): void
    {
        $issues = $this->issuesFor(
            $this->idCheckedCustomer(),
            $this->onHoldSubscription(),
            $this->renewalOrder('failed', Carbon::now()->subDays(2)->toIso8601String()),
        );

        self::assertCount(0, $issues);
    }

    /**
     * Signing someone up leaves their membership on hold until they come in and get their ID checked. That is working
     * as intended, and their orders aren't worth an API call.
     */
    #[Test]
    public function membership_waiting_on_an_id_check_is_not_reported(): void
    {
        $unpaidOrders = Mockery::mock(WooCommerceUnpaidSubscriptionOrders::class);
        $unpaidOrders->shouldNotReceive('forSubscription');

        $issues = $this->checker(
            collect([$this->customer()->id(1)->toArray()]),
            collect([$this->onHoldSubscription()]),
            $unpaidOrders,
            collect([$this->fullMembershipFor(2)]),
        )->getIssues();

        self::assertCount(0, $issues);
    }

    /**
     * Someone can never get their ID checked and still donate every month, so anything other than the subscription
     * behind a full membership gets looked at regardless.
     */
    #[Test]
    public function subscription_that_is_not_a_membership_is_reported_without_an_id_check(): void
    {
        $unpaidOrders = Mockery::mock(WooCommerceUnpaidSubscriptionOrders::class);
        $unpaidOrders->shouldReceive('forSubscription')->with(2)->andReturnNull();

        $issues = $this->checker(
            collect([$this->customer()->id(1)->toArray()]),
            collect([$this->onHoldSubscription()]),
            $unpaidOrders,
        )->getIssues();

        self::assertCount(1, $issues);
        self::assertInstanceOf(SubscriptionOnHoldTooLong::class, $issues->first());
    }

    #[Test]
    public function unpaid_parent_order_is_reported_as_a_failed_first_payment(): void
    {
        $issues = $this->issuesFor(
            $this->idCheckedCustomer(),
            $this->onHoldSubscription(),
            $this->parentOrder('failed', self::LONG_AGO),
        );

        self::assertCount(1, $issues);
        self::assertInstanceOf(SubscriptionFirstPaymentFailed::class, $issues->first());
    }

    #[Test]
    public function on_hold_with_nothing_unpaid_is_reported_as_on_hold_too_long(): void
    {
        $issues = $this->issuesFor(
            $this->idCheckedCustomer(),
            $this->onHoldSubscription(),
            null,
        );

        self::assertCount(1, $issues);
        self::assertInstanceOf(SubscriptionOnHoldTooLong::class, $issues->first());
    }

    #[Test]
    public function recently_changed_subscription_with_nothing_unpaid_is_left_alone(): void
    {
        $subscription = $this->onHoldSubscription();
        $subscription['date_modified'] = Carbon::now()->subDays(2)->toIso8601String();

        $issues = $this->issuesFor($this->idCheckedCustomer(), $subscription, null);

        self::assertCount(0, $issues);
    }

    #[Test]
    #[DataProvider('notOnHoldStatuses')]
    public function subscriptions_that_are_not_on_hold_are_ignored(string $status): void
    {
        $subscription = $this->onHoldSubscription();
        $subscription['status'] = $status;

        $issues = $this->issuesFor(
            $this->idCheckedCustomer(),
            $subscription,
            $this->renewalOrder('failed', self::LONG_AGO),
        );

        self::assertCount(0, $issues);
    }

    public static function notOnHoldStatuses(): array
    {
        return [
            'Active' => ['active'],
            'Cancelled' => ['cancelled'],
            'Expired' => ['expired'],
            'Pending Cancellation' => ['pending-cancel'],
        ];
    }

    private function issuesFor(array $customer, array $subscription, ?array $unpaidOrder): Collection
    {
        $unpaidOrders = Mockery::mock(WooCommerceUnpaidSubscriptionOrders::class);
        $unpaidOrders->shouldReceive('forSubscription')
            ->with($subscription['id'])
            ->andReturn($unpaidOrder);

        return $this->checker(collect([$customer]), collect([$subscription]), $unpaidOrders)->getIssues();
    }

    private function checker(
        Collection $customers,
        Collection $subscriptions,
        $unpaidOrders,
        ?Collection $userMemberships = null
    ): OnHoldSubscriptionIssues {
        $customersCache = Mockery::mock(WooCommerceCustomers::class);
        $customersCache->shouldReceive('get')->andReturn($customers);

        $subscriptionsCache = Mockery::mock(WooCommerceSubscriptions::class);
        $subscriptionsCache->shouldReceive('get')->andReturn($subscriptions);

        $userMembershipsCache = Mockery::mock(WooCommerceUserMemberships::class);
        $userMembershipsCache->shouldReceive('get')->andReturn($userMemberships ?? collect());

        $aggregateCustomerData = new AggregateCustomerData(
            $customersCache,
            $subscriptionsCache,
            $userMembershipsCache
        );

        return new OnHoldSubscriptionIssues($aggregateCustomerData, $subscriptionsCache, $unpaidOrders);
    }

    private function idCheckedCustomer(): array
    {
        return $this->customer()->id(1)->id_was_checked()->toArray();
    }

    private function onHoldSubscription(): array
    {
        $subscription = $this->subscription()->id(2)->customer(1)->status('on-hold');
        $subscription['date_modified'] = self::LONG_AGO;

        return $subscription->toArray();
    }

    private function fullMembershipFor(int $subscriptionId): array
    {
        $userMembership = $this->userMembership()
            ->customer(1)
            ->plan(UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->status('paused');
        $userMembership['subscription_id'] = $subscriptionId;

        return $userMembership->toArray();
    }

    private function parentOrder(string $status, string $dateCreated): array
    {
        return [
            'id' => 1,  // SubscriptionBuilder hands back parent_id 1
            'status' => $status,
            'order_type' => 'parent_order',
            'created_via' => 'checkout',
            'date_created' => $dateCreated,
            'date_paid' => null,
        ];
    }

    private function renewalOrder(string $status, string $dateCreated): array
    {
        return [
            'id' => 3,
            'status' => $status,
            'order_type' => 'renewal_order',
            'created_via' => 'subscription',
            'date_created' => $dateCreated,
            'date_paid' => null,
        ];
    }
}
