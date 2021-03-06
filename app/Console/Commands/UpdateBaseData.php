<?php

namespace App\Console\Commands;

use App\Aggregates\CapabilityAggregate;
use App\Aggregates\MembershipAggregate;
use App\Customer;
use App\Subscription;
use App\UserMembership;
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
        $this->updateCustomerCapabilitiesInDatabase();
        // TODO: Handle updates for email changes

        $this->updateSubscriptionsInDatabase();
        $this->updateUserMembershipsInDatabase();
    }

    /**
     * @throws ApiCallFailed
     */
    private function createCustomersInDatabase()
    {
        $this->line('Updating customers');

        $customersInDB = Customer::all();
        $customersInWooCommerce = $this->api->customers->list();
        $customersInWooCommerce->each(function ($customer) use ($customersInDB) {
            $wooId = $customer['id'];
            if (! $customersInDB->contains('woo_id', $wooId)) {
                $username = $customer['username'];
                $this->line("{$username} was not in our internal store, adding.");
                MembershipAggregate::make($customer['id'])
                    ->importCustomer($customer)
                    ->persist();
            }
        });
    }

    private function updateCustomerCapabilitiesInDatabase()
    {
        $this->line('Updating user capabilities');

        $customersInDB = Customer::all()
            ->whereNull('capabilities');

        $customersInDB->each(function ($customer) {
            /** @var Customer $customer */
            $username = $customer->username;
            $this->line("${username} doesn't have their capabilities set, updating.");
            $capabilities = $this->api->customers->capabilities($customer->woo_id);
            CapabilityAggregate::make($customer->woo_id)
                ->importCapabilities($capabilities)
                ->persist();
        });
    }

    /**
     * @throws ApiCallFailed
     */
    private function updateSubscriptionsInDatabase()
    {
        $this->line('Updating subscriptions');

        $subscriptionsInDB = Subscription::all();
        $subscriptionsInWooCommerce = $this->api->subscriptions->list();
        $subscriptionsInWooCommerce->each(function ($subscription) use ($subscriptionsInDB) {
            $wooId = $subscription['id'];
            if (! $subscriptionsInDB->contains('woo_id', $wooId)) {
                $this->line("Subscription {$wooId} was not in our internal store, adding.");

                MembershipAggregate::make($subscription['customer_id'])
                    ->importSubscription($subscription)
                    ->persist();
            }
        });
    }

    public function updateUserMembershipsInDatabase()
    {
        $this->line('Updating user memberships');

        $userMembershipsInDB = UserMembership::all();
        $userMembershipsInWooCommerce = $this->api->members->list();
        $userMembershipsInWooCommerce->each(function ($membership) use ($userMembershipsInDB) {
            $wooId = $membership['id'];
            if (! $userMembershipsInDB->contains('id', $wooId)) {
                $this->line("User Membership {$wooId} was not in our internal store, adding.");

                MembershipAggregate::make($membership['customer_id'])
                    ->importUserMembership($membership)
                    ->persist();
            }
        });
    }
}
