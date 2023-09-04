<?php

namespace App\Console\Commands;

use App\External\Slack\SlackApi;
use App\External\WooCommerce\Api\ApiCallFailed;
use App\External\WooCommerce\Api\WooCommerceApi;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class MatchSlackUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'denhac:match-slack-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tries to match the woo commerce users with their slack id';

    /**
     * @var WooCommerceApi
     */
    private $wooCommerceApi;

    /**
     * @var SlackApi
     */
    private $slackApi;

    /**
     * Create a new command instance.
     */
    public function __construct(WooCommerceApi $wooCommerceApi, SlackApi $slackApi)
    {
        parent::__construct();
        $this->wooCommerceApi = $wooCommerceApi;
        $this->slackApi = $slackApi;
    }

    /**
     * Execute the console command.
     *
     * @return void
     *
     * @throws ApiCallFailed
     */
    public function handle()
    {
        $this->wooCommerceApi->customers
            ->list()
            ->each(function ($customer) {
                if ($customer['first_name'] == '' || $customer['last_name'] == '') {
                    $this->line("Customer #{$customer['id']} has issues with their name");
                }
            });

        $wooCustomers = $this->getCustomersWithNoSlackId();

        $this->line("There are {$wooCustomers->count()} customers in WooCommerce we can try to look up.");

        $slackMembers = $this->slackApi->users->list();

        $this->line("There are {$slackMembers->count()} members in slack.");

        $members = $slackMembers
            ->filter(function ($member) {
                return ! is_null($member['profile']['email'] ?? null) && ! $member['deleted'] && ! $member['is_bot'];
            });

        $this->line("{$members->count()} of those have an email address and id.");

        $matchingCustomers = collect();

        $wooCustomers->each(function ($customer) use ($members, &$matchingCustomers) {
            $member = $members->first(function ($member) use ($customer) {
                return strtolower($member['profile']['email'] ?? '') == strtolower($customer['email']);
            });
            if (! is_null($member)) {
                $matchingCustomers->push([
                    'woo_id' => $customer['id'],
                    'slack_id' => $member['id'],
                    'email' => $customer['email'],
                    'customer_name' => "{$customer['first_name']} {$customer['last_name']}",
                    'slack_name' => $member['name'],
                ]);
            }
        });

        $this->line("We can match {$matchingCustomers->count()} woo commerce members to their slack account.");

        $matchingCustomers->each(function ($customer) {
            $slack_id = $customer['slack_id'];
            $woo_id = $customer['woo_id'];
            $this->line("Setting slack id {$slack_id} for customer {$customer['customer_name']} with woo id {$woo_id}");

            $this->wooCommerceApi->customers->update($woo_id, [
                'meta_data' => [
                    [
                        'key' => 'access_slack_id',
                        'value' => $slack_id,
                    ],
                ],
            ]);
        });

        $wooCustomers = $this->getCustomersWithNoSlackId();

        $this->line("There are {$wooCustomers->count()} remaining without a slack id.");

        $wooCustomers->each(function ($customer) {
            $this->line('Customer:');
            $this->line(" > Woo ID: {$customer['id']}");
            $this->line(" > URL: https://denhac.org/wp-admin/user-edit.php?user_id={$customer['id']}");
            $this->line(" > Name: {$customer['first_name']} {$customer['last_name']}");
            $this->line(" > Username: {$customer['username']}");
            $this->line('');
        });
    }

    /**
     * @return Collection
     *
     * @throws ApiCallFailed
     */
    private function getCustomersWithNoSlackId()
    {
        return $this->wooCommerceApi->customers->list()
            ->filter(function ($customer) {
                if ($customer['first_name'] == '' || $customer['last_name'] == '') {
                    return false;
                }

                $access_slack_id = collect($customer['meta_data'])
                    ->firstWhere('key', 'access_slack_id') ?? null;

                if (! is_null($access_slack_id) && $access_slack_id != '') {
                    return false;
                }

                return true;
            });
    }
}
