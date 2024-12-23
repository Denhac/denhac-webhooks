<?php

namespace App\Actions\QuickBooks;

use App\External\QuickBooks\QuickBookReferences;
use App\External\QuickBooks\QuickBooksAuthSettings;
use App\External\WooCommerce\Api\WooCommerceApi;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use QuickBooksOnline\API\Data\IPPJournalEntry;
use QuickBooksOnline\API\Data\IPPJournalEntryLineDetail;
use QuickBooksOnline\API\Data\IPPLine;
use QuickBooksOnline\API\DataService\DataService;
use Spatie\QueueableAction\QueueableAction;
use Stripe\StripeClient;

class GenerateVendingNetJournalEntry
{
    use QueueableAction;

    private WooCommerceApi $wooCommerceApi;

    private StripeClient $stripeClient;

    private Collection $products;  // Product id -> VendingProductData. Used to do lazy lookups.

    private QuickBookReferences $quickBookReferences;

    public function __construct(
        StripeClient $stripeClient,
        WooCommerceApi $wooCommerceApi,
        QuickBookReferences $quickBookReferences,
    ) {
        $this->wooCommerceApi = $wooCommerceApi;
        $this->stripeClient = $stripeClient;
        $this->products = collect();
        $this->quickBookReferences = $quickBookReferences;
    }

    public function execute(Carbon $date): void
    {
        if (! QuickBooksAuthSettings::hasKnownAuth()) {
            return;  // We have no QuickBooks auth, we can't do anything
            // TODO This is a silent fail. Which is fine if we don't have credentials but less fine if we accidentally
            // don't have credentials. Also we can't test this locally as easily. Need to add a --dry-run flag or
            // something.
        }

        $begin = Carbon::create(year: $date->year, month: $date->month, day: $date->day, timezone: $date->timezone);
        $end = Carbon::make($begin)->addDay()->subSecond();

        $wooCommerceOrders = $this->wooCommerceApi->orders->list([
            'after' => $begin->toIso8601String(),
            'before' => $end->toIso8601String(),
        ]);

        $totalVendingNet = 0;  // Unit is in pennies
        $totalVendingSpent = 0;  // Unit is in pennies
        $orderLines = collect();

        foreach ($wooCommerceOrders as $wooCommerceOrder) {
            $vendingOrder = new VendingOrderData($wooCommerceOrder);
            if (! $vendingOrder->isValidStripeOrder()) {
                continue;
            }

            // Update our products mapping based on this order's products
            $this->updateProducts($vendingOrder);

            [$orderSpent, $vendingNet, $vendingSpent] = $vendingOrder->getVendingNetAndSpent($this->stripeClient, $this->products);

            if ($vendingSpent == 0) {
                continue;  // Not a vending machine order, ignore it
            }

            $totalVendingNet += $vendingNet;
            $totalVendingSpent += $vendingSpent;

            $description = sprintf(
                "Order #%d\nSpent: \$%4.2f\nVending spent: \$%4.2f\nVending net: \$%4.2f",
                $vendingOrder->id,
                $orderSpent / 100,
                $vendingSpent / 100,
                $vendingNet / 100,
            );

            $lineDetail = new IPPJournalEntryLineDetail([
                'PostingType' => 'Credit',
                'ClassRef' => $this->quickBookReferences->vendingPoolClass,
                'AccountRef' => $this->quickBookReferences->vendingAdjustmentAccountTo,
            ]);
            $orderLines->add(new IPPLine([
                'Description' => $description,
                'Amount' => $vendingNet / 100,
                'JournalEntryLineDetail' => $lineDetail,
                'DetailType' => 'JournalEntryLineDetail',
            ]));
        }

        if ($totalVendingSpent == 0) {
            return;
        }

        $totalNetLineDetail = new IPPJournalEntryLineDetail([
            'PostingType' => 'Debit',
            'AccountRef' => $this->quickBookReferences->vendingAdjustmentAccountFrom,
        ]);
        $totalNetLine = new IPPLine([
            'Amount' => $totalVendingNet / 100,
            'JournalEntryLineDetail' => $totalNetLineDetail,
            'DetailType' => 'JournalEntryLineDetail',
        ]);
        $journalEntry = new IPPJournalEntry([
            'Line' => [
                $totalNetLine,
                ...$orderLines->toArray(),
            ],
            'TxnDate' => $date->toDateString(),
            //            'DocNumber' => "AUTO_GENERATE",
            'PrivateNote' => 'Moving accounts due to mixed transaction types included in a single deposit. Transaction details have been included in the line level description. This entry has been created automatically.',
        ]);

        /** @var DataService $dataService */
        $dataService = app(DataService::class);
        $response = $dataService->Add($journalEntry);
        if (! is_null($response)) {
            return;
        }
        $error = $dataService->getLastError();
        if ($error) {
            throw new \Exception("Error creating journal entry: {$error->getHttpStatusCode()} {$error->getOAuthHelperError()} {$error->getResponseBody()}");
        }
    }

    protected function updateProducts(VendingOrderData $vendingOrderData): void
    {
        foreach ($vendingOrderData->productPricing->keys() as $productId) {
            $this->getProduct($productId); // Just make sure it exists in the products array
        }
    }

    protected function getProduct($productId): VendingProductData
    {
        if (! $this->products->has($productId)) {
            $this->products->put($productId, new VendingProductData($this->wooCommerceApi->products->get($productId)));  // We'll actually get it in a second.
        }

        return $this->products->get($productId);
    }
}
