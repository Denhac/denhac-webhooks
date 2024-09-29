<?php

namespace App\DataCache;

use App\External\Google\GmailEmailHelper;
use App\External\WooCommerce\MetaData;
use App\Models\Waiver;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AggregateCustomerData extends CachedData
{
    public function __construct(
        private readonly WooCommerceCustomers       $wooCommerceCustomers,
        private readonly WooCommerceSubscriptions   $wooCommerceSubscriptions,
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

            $subscriptionMap = $subscriptions->groupBy(fn($sub) => $sub['customer_id']);
            $userMembershipsMap = $userMemberships->groupBy(fn($um) => $um['customer_id']);

            $progress = $this->apiProgress('Compiling WooCommerce members list');
            $progress->setProgress(0, $customers->count());

            return $customers->map(function ($customer) use ($progress, $subscriptionMap, $userMembershipsMap, $waivers) {
                $meta_data = new MetaData($customer['meta_data']);

                $card_string = $meta_data['access_card_number'];
                $cards = is_null($card_string) ? collect() : collect(explode(',', $card_string))
                    ->map(function ($card) {
                        return ltrim($card, '0');
                    });

                $emails = collect();
                $primaryEmail = GmailEmailHelper::handleGmail(Str::lower($customer['email']));
                if (! is_null($customer['email'])) {
                    $emails->push($primaryEmail);
                }

                $email_aliases_string = $meta_data['email_aliases'];
                $email_aliases = is_null($email_aliases_string) ? collect() : collect(explode(',', $email_aliases_string));
                $emails = $emails->merge($email_aliases);

                $idWasChecked = ! is_null($meta_data['id_was_checked']);

                /** @var Collection $customerUserMemberships */
                $customerUserMemberships = $userMembershipsMap->get($customer['id'], fn() => collect());
                $fullMemberUserMembership = $customerUserMemberships->first(fn($um) => $um['plan_id'] == \App\Models\UserMembership::MEMBERSHIP_FULL_MEMBER);

                if (is_null($fullMemberUserMembership)) {
                    $isMember = false;
                } else {
                    $fullMemberUserMembershipStatus = $fullMemberUserMembership['status'];
                    $isMember = in_array(
                        $fullMemberUserMembershipStatus,
                        ['active', 'pending', 'complimentary']
                    );

                    if ($idWasChecked && $fullMemberUserMembershipStatus == 'paused') {
                        // Their ID was checked and it's paused. Could just be a transition or they're failing next months
                        // payment. Either way as of right this second, they're a member.
                        $isMember = true;
                    }
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
                    subscriptions: $subscriptionMap->get($customer['id'], fn() => collect()),
                    userMemberships: $customerUserMemberships,
                    cards: $cards,
                    slackId: $meta_data['access_slack_id'],
                    githubUsername: $meta_data['github_username'],
                    stripeCardHolderId: $meta_data['stripe_card_holder_id'],
                    accessCardTemporaryCode: $meta_data['access_card_temporary_code'],
                );
            });
        });
    }
}
