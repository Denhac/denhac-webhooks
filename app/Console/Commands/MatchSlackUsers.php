<?php

namespace App\Console\Commands;

use App\Slack\SlackApi;
use App\WooCommerce\Api\ApiCallFailed;
use App\WooCommerce\Api\WooCommerceApi;
use Illuminate\Console\Command;

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
     *
     * @param WooCommerceApi $wooCommerceApi
     * @param SlackApi $slackApi
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
     * @throws ApiCallFailed
     */
    public function handle()
    {
        $wooCustomers = $this->wooCommerceApi->customers->list();

        $this->line("There are {$wooCustomers->count()} customers in WooCommerce.");

        $slackMembers = $this->slackApi->users_list();

        $this->line("There are {$slackMembers->count()} members in slack.");

        $members = $slackMembers
            ->map(function ($member) {
                return [
                    "id" => $member["id"] ?? null,
                    "email" => $member["profile"]["email"] ?? null,
                    "name" => $member["name"],
                ];
            })
            ->filter(function ($member) {
                return !is_null($member["email"]);
            });

        $this->line("{$members->count()} of those have an email address and id.");

        $customers = $wooCustomers
            ->map(function ($customer) {
                return [
                    "id" => $customer["id"],
                    "email" => $customer["email"],
                ];
            });

        $matchingCustomers = collect();

        $customers->each(function ($customer) use ($members, &$matchingCustomers) {
            $member = $members->first(function ($member) use ($customer) {
                return $member["email"] == $customer["email"];
            });
            if(! is_null($member)) {
                $matchingCustomers->push([
                    "wooId" => $customer["id"],
                    "slackId" => $member["id"],
                    "email" => $customer["email"],
                    "name" => $member["name"],
                ]);
            }
        });

        $this->line("We can match {$matchingCustomers->count()} woo commerce members to their slack account.");
    }
}
