<?php

namespace App\Console\Commands;

use App\Aggregates\MembershipAggregate;
use App\Customer;
use App\Subscription;
use App\WooCommerce\Api\ApiCallFailed;
use App\WooCommerce\Api\WooCommerceApi;
use Illuminate\Console\Command;

class UpdateBaseData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'denhac:update-base-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates the base layer of data with things we might have missed with WooCommerce';
    /**
     * @var WooCommerceApi
     */
    private $api;

    /**
     * Create a new command instance.
     *
     * @param WooCommerceApi $api
     */
    public function __construct(WooCommerceApi $api)
    {
        parent::__construct();
        $this->api = $api;
    }

    /**
     * Execute the console command.
     *
     * @throws ApiCallFailed
     */
    public function handle()
    {
        $this->createCustomersInDatabase();
        // TODO: Handle updates for email changes

        $this->updateSubscriptionsInDatabase();
    }

    /**
     * @throws ApiCallFailed
     */
    private function createCustomersInDatabase()
    {
        $customersInDB = Customer::all();
        $customersInWooCommerce = $this->api->customers->list();
        $customersInWooCommerce->each(function ($customer) use ($customersInDB) {
            $wooId = $customer["id"];
            if(!$customersInDB->contains("woo_id", $wooId)) {
                $username = $customer["username"];
                $this->line("{$username} was not in our internal store, adding.");
                MembershipAggregate::make($customer["id"])
                    ->createCustomer($customer)
                    ->persist();
            }
        });
    }

    /**
     * @throws ApiCallFailed
     */
    private function updateSubscriptionsInDatabase()
    {
        $subscriptionsInDB = Subscription::all();
        $subscriptionsInWooCommerce = $this->api->subscriptions->list();
        $subscriptionsInWooCommerce->each(function ($subscription) use ($subscriptionsInDB) {
            $wooId = $subscription["id"];
            if(!$subscriptionsInDB->contains("woo_id", $wooId)) {
                $this->line("Subscription {$wooId} was not in our internal store, adding.");

                MembershipAggregate::make($subscription["customer_id"])
                    ->createSubscription($subscription)
                    ->persist();
            }
        });
    }
}
