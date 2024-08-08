<?php

namespace App\DataCache;

use App\External\Google\GmailEmailHelper;
use App\Models\UserMembership;
use App\Models\Waiver;
use Illuminate\Support\Str;

class AggregateCustomerData extends CachedData
{
    public function __construct(
        private readonly WooCommerceCustomers     $wooCommerceCustomers,
        private readonly WooCommerceSubscriptions $wooCommerceSubscriptions,
        private readonly WooCommerceUserMemberships $wooCommerceUserMemberships
    )
    {
        parent::__construct();
    }

    public function get()
    {
        return $this->cache(function () {
            $subscriptions = $this->wooCommerceSubscriptions->get();
            $userMemberships = $this->wooCommerceUserMemberships->get();
            $waivers = Waiver::all();

            $customers = $this->wooCommerceCustomers->get();

            // This has to be created after anything else with a progress bar
            $progress = $this->apiProgress('Compiling WooCommerce members list');
            $progress->setProgress(0, $customers->count());
            return $customers->map(function ($customer) use ($progress, $subscriptions, $userMemberships, $waivers) {
                $meta_data = collect($customer['meta_data']);
                $card_string = $this->getMetaValue($meta_data, 'access_card_number');
                $cards = is_null($card_string) ? collect() : collect(explode(',', $card_string))
                    ->map(function ($card) {
                        return ltrim($card, '0');
                    });

                $emails = collect();
                $primaryEmail = GmailEmailHelper::handleGmail(Str::lower($customer['email']));
                if (! is_null($customer['email'])) {
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

                $fullMemberUserMembershipStatus = $userMembershipsMap->get(UserMembership::MEMBERSHIP_FULL_MEMBER);
                $isMember = in_array(
                    $fullMemberUserMembershipStatus,
                    ['active', 'pending', 'complimentary']
                );

                $idWasChecked = ! is_null($this->getMetaValue($meta_data, 'id_was_checked'));
                if ($idWasChecked && $fullMemberUserMembershipStatus == 'paused') {
                    // Their ID was checked and it's paused. Could just be a transition or they're failing next months
                    // payment. Either way as of right this second, they're a member.
                    $isMember = true;
                }

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
                );
            });
        });
    }

    private function getMetaValue($meta_data, $key)
    {
        $meta_entry = $meta_data->where('key', $key)->first();

        return is_null($meta_entry) ? null : ($meta_entry['value'] ?: null);
    }
}
