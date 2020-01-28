<?php

namespace App\Console\Commands;

use App\ActiveCardHolderUpdate;
use App\PaypalBasedMember;
use App\Slack\SlackApi;
use App\Subscription;
use App\WooCommerce\Api\ApiCallFailed;
use App\WooCommerce\Api\WooCommerceApi;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;

class IdentifyIssues extends Command
{
    const ISSUE_WITH_AN_ACTIVE_CARD = "Issue with an active card";
    const ISSUE_SLACK_ACCOUNT = "Issue with a Slack account";

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'denhac:identify-issues';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Identifies issues with membership and access';

    /**
     * @var MessageBag
     */
    private $issues;
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
     */
    public function __construct(WooCommerceApi $wooCommerceApi, SlackApi $slackApi)
    {
        parent::__construct();

        $this->issues = new MessageBag();
        $this->wooCommerceApi = $wooCommerceApi;
        $this->slackApi = $slackApi;
    }

    /**
     * Execute the console command.
     *
     * @throws ApiCallFailed
     */
    public function handle()
    {
        $this->info("Identifying issues");
        $members = $this->getMembers();
        $this->unknownActiveCard($members);
        $this->extraSlackUsers($members);

        $this->printIssues();
    }


    private function printIssues()
    {
        collect($this->issues->keys())
            ->each(function ($key) {
                $knownIssues = collect($this->issues->get($key));
                $this->info("$key ({$knownIssues->count()})");

                $knownIssues
                    ->map(function ($issue) {
                        $this->info(">>> $issue");
                    });
                $this->info("");
            });
    }

    /**
     * @throws ApiCallFailed
     */
    private function getMembers()
    {
        $customers = $this->wooCommerceApi->customers->list();

        $subscriptions = $this->wooCommerceApi->subscriptions->list();

        $members = $customers->map(function ($customer) use ($subscriptions) {
            $isMember = $subscriptions
                ->where('customer_id', $customer['id'])
                ->where('status', 'active')
                ->isNotEmpty();

            $meta_data = collect($customer['meta_data']);
            $card_string = $meta_data->where('key', 'access_card_number')->first()['value'];
            $cards = is_null($card_string) ? collect() : collect(explode(",", $card_string))
                ->map(function ($card) {
                    return ltrim($card, "0");
                });

            return [
                "first_name" => $customer['first_name'],
                "last_name" => $customer['last_name'],
                "is_member" => $isMember,
                "cards" => $cards,
                "slack_id" => $meta_data->where('key', 'access_slack_id')->first()['value'],
            ];
        });

        $members = $members->concat(PaypalBasedMember::all()
            ->map(function ($member) {
                return [
                    "first_name" => $member->first_name,
                    "last_name" => $member->last_name,
                    "is_member" => $member->active,
                    "cards" => collect([$member->card]),
                    "slack_id" => $member->slack_id,
                ];
            }));

        return $members;
    }

    /**
     * Identify any issues where there is an active card listed for someone, but we have no record of them being an
     * active member.
     * @param $members
     */
    private function unknownActiveCard(Collection $members)
    {
        /** @var ActiveCardHolderUpdate $activeCardHolderUpdate */
        $activeCardHolderUpdate = ActiveCardHolderUpdate::latest()->first();
        if (is_null($activeCardHolderUpdate)) {
            return;
        }

        $card_holders = $activeCardHolderUpdate->card_holders;
        collect($card_holders)
            ->each(function ($card_holder) use ($members) {
                $membersWithCard = $members
                    ->filter(function ($member) use ($card_holder) {
                        return $member['cards']->contains($card_holder["card_num"]);
                    });

                if ($membersWithCard->count() == 0) {
                    $message = "{$card_holder["first_name"]} {$card_holder["last_name"]} has the active card ({$card_holder["card_num"]}) but I have no membership record of them with that card.";
                    $this->issues->add(self::ISSUE_WITH_AN_ACTIVE_CARD, $message);

                    return;
                }

                if ($membersWithCard->count() > 1) {
                    $message = "{$card_holder["first_name"]} {$card_holder["last_name"]} has the active card ({$card_holder["card_num"]}) but is connected to multiple accounts.";
                    $this->issues->add(self::ISSUE_WITH_AN_ACTIVE_CARD, $message);

                    return;
                }

                $member = $membersWithCard->first();

                if ($card_holder["first_name"] != $member["first_name"] ||
                    $card_holder["last_name"] != $member["last_name"]) {
                    $message = "{$card_holder["first_name"]} {$card_holder["last_name"]} has the active card ({$card_holder["card_num"]}) but is listed as {$member["first_name"]} {$member["last_name"]} in our records.";
                    $this->issues->add(self::ISSUE_WITH_AN_ACTIVE_CARD, $message);
                }

                if (!$member["is_member"]) {
                    $message = "{$card_holder["first_name"]} {$card_holder["last_name"]} has the active card ({$card_holder["card_num"]}) but is not currently a member.";
                    $this->issues->add(self::ISSUE_WITH_AN_ACTIVE_CARD, $message);
                }
            });
    }

    // TODO Add function for members who don't have a slack invite
    private function extraSlackUsers(Collection $members)
    {
        $users = $this->slackApi->users_list()
            ->filter(function ($user) {
                if (array_key_exists("is_bot", $user) && $user["is_bot"]) {
                    return false;
                }

                if (array_key_exists("deleted", $user) && $user["deleted"]) {
                    return false;
                }

                if (array_key_exists("is_restricted", $user) && $user["is_restricted"]) {
                    return false;
                }
                if (array_key_exists("is_ultra_restricted", $user) && $user["is_ultra_restricted"]) {
                    return false;
                }

                // TODO Maybe move this down so we can handle the case where someone is a member but doesn't have a full privilege slack account?

                return true;
            });

        $users
            ->each(function ($user) use ($members) {
                $membersForSlackId = $members
                    ->filter(function ($member) use ($user) {
                        return $member["slack_id"] == $user["id"];
                    });

                if ($membersForSlackId->count() == 0) {
                    $message = "{$user["name"]} with slack id ({$user["id"]}) is a full user in slack but I have no membership record of them.";
                    $this->issues->add(self::ISSUE_SLACK_ACCOUNT, $message);
                    return;
                }

                $member = $membersForSlackId->first();

                if (!$member["is_member"]) {
                    $message = "{$member["first_name"]} {$member["last_name"]} with slack id ({$user["id"]}) is not an active member but they have a full slack account.";
                    $this->issues->add(self::ISSUE_SLACK_ACCOUNT, $message);
                }
            });
    }
}
