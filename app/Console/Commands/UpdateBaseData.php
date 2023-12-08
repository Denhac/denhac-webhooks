<?php

namespace App\Console\Commands;

use App\Aggregates\MembershipAggregate;
use App\External\WooCommerce\Api\ApiCallFailed;
use App\External\WooCommerce\Api\WooCommerceApi;
use App\Models\Customer;
use App\Models\Subscription;
use App\Models\UserMembership;
use Illuminate\Console\Command;

class UpdateBaseData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'denhac:update-base-data {--dry-run}';

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

    private bool $isDryRun = false;

    /**
     * Create a new command instance.
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
    public function handle(): void
    {
        $this->isDryRun = $this->option('dry-run');
        if ($this->isDryRun) {
            $this->line('Dry run, will not actually update anything.');
        }

        $this->createCustomersInDatabase();
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
            if (! $customersInDB->contains('id', $wooId)) {
                $username = $customer['username'];
                $this->line("{$username} was not in our internal store, adding.");
                if (! $this->isDryRun) {
                    MembershipAggregate::make($customer['id'])
                        ->importCustomer($customer)
                        ->persist();
                }
            }
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
            if (! $subscriptionsInDB->contains('id', $wooId)) {
                $this->line("Subscription {$wooId} was not in our internal store, adding.");

                if (! $this->isDryRun) {
                    MembershipAggregate::make($subscription['customer_id'])
                        ->importSubscription($subscription)
                        ->persist();
                }
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

                if (! $this->isDryRun) {
                    MembershipAggregate::make($membership['customer_id'])
                        ->importUserMembership($membership)
                        ->persist();
                }
            }
        });
    }
}
