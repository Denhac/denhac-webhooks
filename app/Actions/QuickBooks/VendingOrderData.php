<?php

namespace App\Actions\QuickBooks;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;
use Stripe\Charge;
use Stripe\StripeClient;

class VendingOrderData extends Data
{
    public int $id;

    public ?string $stripeIntentId = null;

    public Collection $productPricing;

    public function __construct(
        public array $orderData,
    ) {
        $this->id = $this->orderData['id'];

        $this->stripeIntentId = collect($orderData['meta_data'])
            ->where('key', '_stripe_intent_id')
            ->first(default: ['value' => null])['value'];

        $this->productPricing = collect();
        foreach ($this->orderData['line_items'] as $lineItem) {
            if ($lineItem['variation_id'] != 0) {
                $productId = $lineItem['variation_id'];
            } else {
                $productId = $lineItem['product_id'];
            }
            $totalPrice = $lineItem['quantity'] * $lineItem['price'];
            $this->productPricing->put($productId, $totalPrice);
        }
    }

    public function isValidStripeOrder(): bool
    {
        if ($this->orderData['payment_method'] != 'stripe') {
            return false;  // We only care about Stripe orders
        }

        if ($this->orderData['status'] != 'completed') {
            return false;  // We only care about completed orders
        }

        if (is_null($this->stripeIntentId)) {
            return false;  // No stripe source id basically means no payment was needed. Coupon probably took care of it all.
        }

        return true;
    }

    protected function hasVendingMachineOrders($products): bool
    {
        /** @var VendingProductData $vendingProductData */
        foreach ($products as $productId => $vendingProductData) {
            if (! $this->productPricing->has($productId)) {
                continue;  // This order doesn't have that product, ignore it
            }

            if ($vendingProductData->isVendingOrder()) {
                return true;
            }
        }

        return false;
    }

    public function getVendingNetAndSpent(StripeClient $stripeClient, Collection $products): array
    {
        if (! $this->hasVendingMachineOrders($products)) {
            return [0, 0, 0];
        }

        $paymentIntent = $stripeClient->paymentIntents->retrieve($this->stripeIntentId);
        if ($paymentIntent->charges->count() != 1) {
            throw new \Exception("More than one charge for $this->stripeIntentId, only testing the first one");
        }

        /** @var Charge $charge */
        $charge = $paymentIntent->charges->data[0];
        if (! $charge->paid) {
            throw new \Exception("Charge for $this->stripeIntentId is not paid");
        }
        $balanceTxId = $charge->balance_transaction;
        $balanceTx = $stripeClient->balanceTransactions->retrieve($balanceTxId);

        $actualSpent = $this->productPricing->values()->sum() * 100;  // Stripe uses units of pennies
        if ($balanceTx->amount != $actualSpent) {
            throw new \Exception("For $this->stripeIntentId, balance says $balanceTx->amount, actual is $actualSpent");
        }

        $vendingSpent = $this->productPricing
            ->filter(fn ($v, $k) => $products->keys()->contains($k))
            ->values()->sum() * 100;

        $vendingNet = ($vendingSpent * $balanceTx->net) / $actualSpent;

        return [$actualSpent, $vendingNet, $vendingSpent];
    }
}
