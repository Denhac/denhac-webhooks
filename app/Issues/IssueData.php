<?php

namespace App\Issues;


use App\External\HasApiProgressBar;
use App\Google\GmailEmailHelper;
use App\Google\GoogleApi;
use App\PaypalBasedMember;
use App\Slack\SlackApi;
use App\UserMembership;
use App\Waiver;
use App\WooCommerce\Api\ApiCallFailed;
use App\WooCommerce\Api\WooCommerceApi;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This is a re-usable set of issue data that can be used with all of the issue checkers
 */
class IssueData
{
    use HasApiProgressBar;

    public const SYSTEM_WOOCOMMERCE = 'WooCommerce';
    public const SYSTEM_PAYPAL = 'PayPal';

    private WooCommerceApi $wooCommerceApi;
    private SlackApi $slackApi;
    private GoogleApi $googleApi;

    private Collection|null $_wooCommerceCustomers = null;
    private Collection|null $_wooCommerceSubscriptions = null;
    private Collection|null $_wooCommerceUserMemberships = null;

    private Collection|null $_slackUsers = null;

    private Collection|null $_members = null;

    private Collection|null $_googleGroups = null;
    private Collection|null $_googleGroupMembers = null;  // Key is group, value is member list

    private OutputInterface|null $output = null;

    public function __construct(
        WooCommerceApi $wooCommerceApi,
        SlackApi       $slackApi,
        GoogleApi      $googleApi,
    )
    {
        $this->wooCommerceApi = $wooCommerceApi;
        $this->slackApi = $slackApi;
        $this->googleApi = $googleApi;
    }

    public function setOutput(OutputInterface|null $output): void
    {
        $this->output = $output;
    }

    public function wooCommerceCustomers(): Collection
    {
        if (is_null($this->_wooCommerceCustomers)) {
            $this->_wooCommerceCustomers = $this->wooCommerceApi->customers->list($this->apiProgress("Fetching WooCommerce Customers"));
        }

        return $this->_wooCommerceCustomers;
    }

    public function wooCommerceSubscriptions(): Collection
    {
        if (is_null($this->_wooCommerceSubscriptions)) {
            $this->_wooCommerceSubscriptions = $this->wooCommerceApi->subscriptions->list($this->apiProgress("Fetching WooCommerce Subscriptions"));
        }

        return $this->_wooCommerceSubscriptions;
    }

    public function wooCommerceUserMemberships(): Collection
    {
        if (is_null($this->_wooCommerceUserMemberships)) {
            $this->_wooCommerceUserMemberships = $this->wooCommerceApi->members->list($this->apiProgress("Fetching WooCommerce User Memberships"));
        }

        return $this->_wooCommerceUserMemberships;
    }

    public function members()
    {
        if (is_null($this->_members)) {

            $subscriptions = $this->wooCommerceSubscriptions();
            $userMemberships = $this->wooCommerceUserMemberships();
            $waivers = Waiver::all();  // TODO Do we need to hit up waiver forever instead of trusting our local db?

            $customers = $this->wooCommerceCustomers();

            // This has to be created after anything else with a progress bar
            $progress = $this->apiProgress("Compiling WooCommerce members list");
            $progress->setProgress(0, $customers->count());
            $this->_members = $customers->map(function ($customer) use ($progress, $subscriptions, $userMemberships, $waivers) {
                $meta_data = collect($customer['meta_data']);
                $card_string = $this->getMetaValue($meta_data, 'access_card_number');
                $cards = is_null($card_string) ? collect() : collect(explode(',', $card_string))
                    ->map(function ($card) {
                        return ltrim($card, '0');
                    });

                $emails = collect();
                if (!is_null($customer['email'])) {
                    $emails->push(GmailEmailHelper::handleGmail(Str::lower($customer['email'])));
                }

                $email_aliases_string = $this->getMetaValue($meta_data, 'email_aliases');
                $email_aliases = is_null($email_aliases_string) ? collect() : collect(explode(',', $email_aliases_string));
                $emails = $emails->merge($email_aliases);

                $subscriptionMap = $subscriptions
                    ->where('customer_id', $customer['id'])
                    ->map(function ($subscription) {
                        return $subscription['status'];
                    });

                $userMembershipsMap = $userMemberships
                    ->where('customer_id', $customer['id'])
                    ->mapWithKeys(function ($userMembership) {
                        return [$userMembership['plan_id'] => $userMembership['status']];
                    });

                $isMember = in_array(
                    $userMembershipsMap->get(UserMembership::MEMBERSHIP_FULL_MEMBER),
                    ["active", "pending", "complimentary"]
                );

                $hasSignedWaiver = $waivers
                    ->where('customer_id', $customer['id'])
                    ->where('template_id', Waiver::getValidMembershipWaiverId())
                    ->isNotEmpty();

                $progress->step();

                return [
                    'id' => $customer['id'],
                    'first_name' => $customer['first_name'],
                    'last_name' => $customer['last_name'],
                    'email' => $emails,
                    'is_member' => $isMember,
                    'has_signed_waiver' => $hasSignedWaiver,
                    'subscriptions' => $subscriptionMap,
                    'user_memberships' => $userMembershipsMap,
                    'cards' => $cards,
                    'slack_id' => $this->getMetaValue($meta_data, 'access_slack_id'),
                    'system' => self::SYSTEM_WOOCOMMERCE,
                ];
            });

            $this->_members = $this->_members->concat(PaypalBasedMember::all()
                ->map(function ($member) {
                    $emails = collect();
                    if (!is_null($member->email)) {
                        $emails->push(GmailEmailHelper::handleGmail(Str::lower($member->email)));
                    }

                    return [
                        'id' => $member->paypal_id,
                        'first_name' => $member->first_name,
                        'last_name' => $member->last_name,
                        'email' => $emails,
                        'is_member' => $member->active,
                        'has_signed_waiver' => false,  # No way for me to actually handle this.
                        'subscriptions' => collect(),
                        'cards' => is_null($member->card) ? collect() : collect([$member->card]),
                        'slack_id' => $member->slack_id,
                        'system' => self::SYSTEM_PAYPAL,
                    ];
                }));
        }
        return $this->_members;
    }

    private function getMetaValue($meta_data, $key)
    {
        $meta_entry = $meta_data->where('key', $key)->first();
        return is_null($meta_entry) ? null : ($meta_entry['value'] ?: null);
    }

    public function slackUsers()
    {
        if (is_null($this->_slackUsers)) {
            $this->_slackUsers = $this->slackApi->users->list($this->apiProgress("Fetching Slack users"));
        }

        return $this->_slackUsers;
    }

    public function googleGroups()
    {
        if (is_null($this->_googleGroups)) {
            $this->_googleGroups = $this->googleApi->groupsForDomain('denhac.org');
        }

        return $this->_googleGroups;
    }

    public function googleGroupMembers($group)
    {
        if (is_null($this->_googleGroupMembers)) {
            $this->_googleGroupMembers = collect();
        }

        if (!$this->_googleGroupMembers->has($group)) {
            $members = $this->googleApi->group($group)->list($this->apiProgress("Fetching Google Group Members $group"));
            $this->_googleGroupMembers->put($group, $members);
        }

        return $this->_googleGroupMembers->get($group);
    }
}
