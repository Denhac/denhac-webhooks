<?php

namespace App\Issues;


use App\External\GitHub\GitHubApi;
use App\External\Google\GmailEmailHelper;
use App\External\Google\GoogleApi;
use App\External\HasApiProgressBar;
use App\External\Slack\SlackApi;
use App\External\WooCommerce\Api\WooCommerceApi;
use App\Issues\Data\MemberData;
use App\PaypalBasedMember;
use App\UserMembership;
use App\Waiver;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Stripe\StripeClient;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This is a re-usable set of issue data that can be used with all of the issue checkers. Ideally all data returned
 * from here is structured, meaning it uses a class in the App\Issues\Data namespace. This does add overhead to marshal
 * into those data types, but it allows us to better type hint issue types and not use magic dictionary keys.
 */
class IssueData
{
    use HasApiProgressBar;

    public const SYSTEM_WOOCOMMERCE = 'WooCommerce';
    public const SYSTEM_PAYPAL = 'PayPal';

    private WooCommerceApi $wooCommerceApi;
    private SlackApi $slackApi;
    private GoogleApi $googleApi;
    private GitHubApi $gitHubApi;

    private Collection|null $_wooCommerceCustomers = null;
    private Collection|null $_wooCommerceSubscriptions = null;
    private Collection|null $_wooCommerceUserMemberships = null;

    private Collection|null $_slackUsers = null;

    private Collection|null $_members = null;

    private Collection|null $_googleGroups = null;
    private Collection|null $_googleGroupMembers = null;  // Key is group, value is member list

    private Collection|null $_gitHubTeamMembers = null;  // The GitHub team is called "members"

    private Collection|null $_stripeCardHolders = null;

    private OutputInterface|null $output = null;

    public function __construct(
        WooCommerceApi $wooCommerceApi,
        SlackApi       $slackApi,
        GoogleApi      $googleApi,
        GitHubApi      $gitHubApi,
    )
    {
        $this->wooCommerceApi = $wooCommerceApi;
        $this->slackApi = $slackApi;
        $this->googleApi = $googleApi;
        $this->gitHubApi = $gitHubApi;
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

    /**
     * @return Collection<MemberData>
     */
    public function members(): Collection
    {
        if (is_null($this->_members)) {

            $subscriptions = collect(); // $this->wooCommerceSubscriptions();  TODO Revert this
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
                $primaryEmail = GmailEmailHelper::handleGmail(Str::lower($customer['email']));
                if (!is_null($customer['email'])) {
                    $emails->push($primaryEmail);
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

                return new MemberData(
                    id: $customer['id'],
                    first_name: $customer['first_name'],
                    last_name: $customer['last_name'],
                    primaryEmail: $primaryEmail,
                    emails: $emails,
                    isMember: $isMember,
                    hasSignedWaiver: $hasSignedWaiver,
                    subscriptions: $subscriptionMap,
                    userMemberships: $userMembershipsMap,
                    cards: $cards,
                    slackId: $this->getMetaValue($meta_data, 'access_slack_id'),
                    githubUsername: $this->getMetaValue($meta_data, 'github_username'),
                    stripeCardHolderId: $this->getMetaValue($meta_data, 'stripe_card_holder_id'),
                    system: self::SYSTEM_WOOCOMMERCE,
                );
            });

            $this->_members = $this->_members->concat(PaypalBasedMember::all()
                ->map(function ($member) {
                    $emails = collect();
                    if (!is_null($member->email)) {
                        $emails->push(GmailEmailHelper::handleGmail(Str::lower($member->email)));
                    }

                    return new MemberData(
                        id: $member->paypal_id,
                        first_name: $member->first_name,
                        last_name: $member->last_name,
                        primaryEmail: $member->email,
                        emails: $emails,
                        isMember: $member->active,
                        hasSignedWaiver: false,  # No way for me to actually handle this.
                        subscriptions: collect(),
                        userMemberships: collect(),
                        cards: is_null($member->card) ? collect() : collect([$member->card]),
                        slackId: $member->slack_id,
                        githubUsername: null,
                        stripeCardHolderId: null,
                        system: self::SYSTEM_PAYPAL,
                    );
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

    public function gitHubTeamMembers(): Collection
    {
        if (is_null($this->_gitHubTeamMembers)) {
            // TODO Deduplicate "members" here
            $this->_gitHubTeamMembers = $this->gitHubApi->team("members")
                ->list($this->apiProgress("Fetching members of GitHub team 'members'"));
        }

        return $this->_gitHubTeamMembers;
    }

    public function stripeCardHolders(): Collection
    {
        if (is_null($this->_stripeCardHolders)) {
            /** @var StripeClient $stripeClient */
            $stripeClient = app(StripeClient::class);
            $this->_stripeCardHolders = collect($stripeClient->issuing->cardholders->all()['data']);
        }

        return $this->_stripeCardHolders;
    }
}
