<?php

namespace App\DataCache;

use App\External\WooCommerce\Api\WooCommerceApi;
use App\External\WooCommerce\MetaData;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Subscription orders that never got paid, looked up by the subscription they belong to.
 *
 * The orders endpoint only links a renewal order back to its subscription through the _subscription_renewal meta key,
 * so we have to do that mapping ourselves. Pulling every unpaid order we've ever had is a lot of pages for very little
 * gain, so we grab a recent window of those up front. Anything the window doesn't cover, we ask about one subscription
 * at a time.
 */
class WooCommerceUnpaidSubscriptionOrders extends CachedData
{
    /**
     * An order that failed and one that is sitting at pending are the same thing to us: money we never collected. Which
     * of the two a stalled renewal lands on depends on where in the retry rules it stopped.
     */
    public const array UNPAID_STATUSES = ['failed', 'pending'];

    private const int RECENT_WINDOW_MONTHS = 12;

    public function __construct(
        private readonly WooCommerceApi $wooCommerceApi
    ) {
        parent::__construct();
    }

    /**
     * The newest unpaid order for this subscription, or null if everything got paid.
     */
    public function forSubscription(int $subscriptionId): ?array
    {
        $recent = $this->recentWindow();

        if ($recent->has($subscriptionId)) {
            /** @noinspection PhpIncompatibleReturnTypeInspection */
            return $recent->get($subscriptionId);
        }

        return $this->cache("subscription-$subscriptionId", function () use ($subscriptionId) {
            return $this->wooCommerceApi->subscriptions->orders($subscriptionId)
                ->filter(fn ($order) => in_array($order['status'], self::UNPAID_STATUSES))
                ->sortByDesc('date_created')
                ->first();
        });
    }

    /**
     * @return Collection Subscription id to the newest unpaid renewal order we found for it
     */
    private function recentWindow(): Collection
    {
        return $this->cache('recent-window', function () {
            $orders = $this->wooCommerceApi->orders->list([
                'status' => implode(',', self::UNPAID_STATUSES),
                'after' => Carbon::now()->subMonths(self::RECENT_WINDOW_MONTHS)->toIso8601String(),
            ], $this->apiProgress('Fetching WooCommerce unpaid orders'));

            $bySubscription = collect();

            foreach ($orders as $order) {
                $subscriptionId = new MetaData($order['meta_data'])['_subscription_renewal'];

                if (is_null($subscriptionId)) {
                    continue;  // Not a renewal order, so it isn't ours to worry about
                }

                $subscriptionId = (int) $subscriptionId;
                $existing = $bySubscription->get($subscriptionId);

                if (is_null($existing) || $order['date_created'] > $existing['date_created']) {
                    $bySubscription->put($subscriptionId, $order);
                }
            }

            return $bySubscription;
        });
    }
}
