<?php

namespace App\Actions\QuickBooks;

use App\External\WooCommerce\Api\WooCommerceApi;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Spatie\QueueableAction\QueueableAction;
use Stripe\StripeClient;

class GenerateVendingNetJournalEntry
{
    use QueueableAction;

    private WooCommerceApi $wooCommerceApi;
    private StripeClient $stripeClient;

    private Collection $products;  // Product id -> VendingProductData. Used to do lazy lookups.

    public function __construct(
        StripeClient   $stripeClient,
        WooCommerceApi $wooCommerceApi
    )
    {
        $this->wooCommerceApi = $wooCommerceApi;
        $this->stripeClient = $stripeClient;
        $this->products = collect();
    }

    public function execute(Carbon $begin, Carbon $end): void
    {
        $wooCommerceOrders = $this->wooCommerceApi->orders->list([
            'after' => $begin->toIso8601String(),
            'before' => $end->toIso8601String(),
        ]);

        $totalOrderSpent = 0;  // Unit is in pennies
        $totalVendingNet = 0;  // Unit is in pennies
        $totalVendingSpent = 0;  // Unit is in pennies
        $orderStrings = collect();

        foreach ($wooCommerceOrders as $wooCommerceOrder) {
            $vendingOrder = new VendingOrderData($wooCommerceOrder);
            if (!$vendingOrder->isValidStripeOrder()) {
                continue;
            }

            // Update our products mapping based on this order's products
            $this->updateProducts($vendingOrder);

            list($orderSpent, $vendingNet, $vendingSpent) = $vendingOrder->getVendingNetAndSpent($this->stripeClient, $this->products);

            if ($vendingSpent == 0) {
                continue;  // Not a vending machine order, ignore it
            }

            $totalOrderSpent += $orderSpent;
            $totalVendingNet += $vendingNet;
            $totalVendingSpent += $vendingSpent;

            $orderString = sprintf("#%d", $vendingOrder->id);
            $orderSpentString = sprintf("\$%4.2f", $orderSpent / 100);
            $vendingSpentString = sprintf("\$%4.2f", $vendingSpent / 100);
            $vendingNetString = sprintf("\$%4.2f", $vendingNet / 100);

            $orderString = sprintf(
                "Order %7s | %8s | %8s | %8s | Vending",
                $orderString,
                $orderSpentString,
                $vendingSpentString,
                $vendingNetString
            );
            $orderStrings->add($orderString);
        }

        $finalStrings = collect();
        $finalStrings->add("Start: {$begin->toDayDateTimeString()}");
        $finalStrings->add("End: {$end->toDayDateTimeString()}");
        $finalStrings->add("Total vending machine orders where Stripe was used: {$orderStrings->count()}");
        $finalStrings->add(sprintf("Total Stripe spent: \$%4.2f", $totalOrderSpent / 100));
        $finalStrings->add(sprintf("Total spent on the vending machine: \$%4.2f", $totalVendingSpent / 100));
        $finalStrings->add(sprintf("Total vending net: \$%4.2f", $totalVendingNet / 100));
        $finalStrings->add("");
        $finalStrings->add(" Order Number |    Total |     Vend |      Net |    Type");
        $finalStrings->add("--------------------------------------------------------");
        $finalStrings = $finalStrings->concat($orderStrings);
        error_log($finalStrings->implode("\n"));
    }

    protected function updateProducts(VendingOrderData $vendingOrderData): void
    {
        foreach ($vendingOrderData->productPricing->keys() as $productId) {
            $this->getProduct($productId); // Just make sure it exists in the products array
        }
    }

    protected function getProduct($productId): VendingProductData
    {
        if (!$this->products->has($productId)) {
            $this->products->put($productId, new VendingProductData($this->wooCommerceApi->products->get($productId)));  // We'll actually get it in a second.
        }

        return $this->products->get($productId);
    }
}
