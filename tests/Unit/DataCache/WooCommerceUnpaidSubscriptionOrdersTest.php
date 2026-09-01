<?php

namespace Tests\Unit\DataCache;

use App\DataCache\WooCommerceUnpaidSubscriptionOrders;
use App\External\WooCommerce\Api\OrdersApi;
use App\External\WooCommerce\Api\subscriptions\SubscriptionsApi;
use App\External\WooCommerce\Api\WooCommerceApi;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WooCommerceUnpaidSubscriptionOrdersTest extends TestCase
{
    private $ordersApi;

    private $subscriptionsApi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ordersApi = Mockery::mock(OrdersApi::class);
        $this->subscriptionsApi = Mockery::mock(SubscriptionsApi::class);
    }

    #[Test]
    public function newest_unpaid_renewal_in_the_recent_window_is_used(): void
    {
        $this->recentWindowReturns(
            $this->unpaidOrder(10, 'failed', '2020-06-01T00:00:00', 200),
            $this->unpaidOrder(11, 'pending', '2020-07-01T00:00:00', 200),
        );
        $this->subscriptionsApi->shouldNotReceive('orders');

        $order = $this->cache()->forSubscription(200);

        self::assertEquals(11, $order['id']);
    }

    #[Test]
    public function orders_that_are_not_renewals_are_not_matched_to_a_subscription(): void
    {
        $this->recentWindowReturns([
            'id' => 10,
            'status' => 'failed',
            'date_created' => '2020-06-01T00:00:00',
            'meta_data' => [],
        ]);
        $this->subscriptionsApi->shouldReceive('orders')->with(200)->andReturn(collect());

        self::assertNull($this->cache()->forSubscription(200));
    }

    #[Test]
    public function subscription_outside_the_recent_window_falls_back_to_its_own_orders(): void
    {
        $this->recentWindowReturns();
        $this->subscriptionsApi->shouldReceive('orders')
            ->with(201)
            ->once()
            ->andReturn(collect([
                $this->subscriptionOrder(20, 'completed', '2024-01-01T00:00:00', 'parent_order'),
                $this->subscriptionOrder(21, 'completed', '2024-02-01T00:00:00'),
                $this->subscriptionOrder(22, 'failed', '2024-03-01T00:00:00'),
            ]));

        $order = $this->cache()->forSubscription(201);

        self::assertEquals(22, $order['id']);
    }

    /**
     * A parent order that was never paid means the very first checkout went wrong. The recent window can't find those,
     * since only renewals carry the meta key pointing back at the subscription, so the fallback has to hand it over.
     */
    #[Test]
    public function unpaid_parent_order_is_handed_back_by_the_fallback(): void
    {
        $this->recentWindowReturns();
        $this->subscriptionsApi->shouldReceive('orders')
            ->with(202)
            ->andReturn(collect([
                $this->subscriptionOrder(20, 'failed', '2020-01-01T00:00:00', 'parent_order'),
            ]));

        $order = $this->cache()->forSubscription(202);

        self::assertEquals(20, $order['id']);
    }

    #[Test]
    public function a_subscription_with_everything_paid_has_no_unpaid_order(): void
    {
        $this->recentWindowReturns();
        $this->subscriptionsApi->shouldReceive('orders')
            ->with(203)
            ->andReturn(collect([
                $this->subscriptionOrder(20, 'completed', '2020-01-01T00:00:00', 'parent_order'),
                $this->subscriptionOrder(21, 'completed', '2020-02-01T00:00:00'),
            ]));

        self::assertNull($this->cache()->forSubscription(203));
    }

    #[Test]
    public function the_fallback_is_only_asked_about_a_subscription_once(): void
    {
        $this->recentWindowReturns();
        $this->subscriptionsApi->shouldReceive('orders')->with(201)->once()->andReturn(collect());

        $cache = $this->cache();
        $cache->forSubscription(201);
        $cache->forSubscription(201);

        self::assertNull($cache->forSubscription(201));
    }

    private function cache(): WooCommerceUnpaidSubscriptionOrders
    {
        // Mockery leaves __get alone, so we hand back the two sub apis ourselves rather than let the real one build a
        // guzzle client we have no use for here.
        $wooCommerceApi = new class($this->ordersApi, $this->subscriptionsApi) extends WooCommerceApi
        {
            public function __construct(
                private $ordersApi,
                private $subscriptionsApi
            ) {}

            public function __get($name)
            {
                return match ($name) {
                    'orders' => $this->ordersApi,
                    'subscriptions' => $this->subscriptionsApi,
                    default => null,
                };
            }
        };

        return new WooCommerceUnpaidSubscriptionOrders($wooCommerceApi);
    }

    private function recentWindowReturns(array ...$orders): void
    {
        $this->ordersApi->shouldReceive('list')
            ->once()
            ->andReturn(collect($orders));
    }

    private function unpaidOrder(int $id, string $status, string $dateCreated, int $subscriptionId): array
    {
        return [
            'id' => $id,
            'status' => $status,
            'date_created' => $dateCreated,
            'meta_data' => [
                ['id' => 1, 'key' => '_subscription_renewal', 'value' => (string) $subscriptionId],
                ['id' => 2, 'key' => '_failed_renewal_order', 'value' => 'yes'],
            ],
        ];
    }

    private function subscriptionOrder(int $id, string $status, string $dateCreated, string $orderType = 'renewal_order'): array
    {
        return [
            'id' => $id,
            'status' => $status,
            'date_created' => $dateCreated,
            'order_type' => $orderType,
            'created_via' => $orderType == 'parent_order' ? 'checkout' : 'subscription',
        ];
    }
}
