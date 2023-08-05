<?php

namespace App\Issues;


use App\External\HasApiProgressBar;
use App\Google\GmailEmailHelper;
use App\PaypalBasedMember;
use App\WooCommerce\Api\ApiCallFailed;
use App\WooCommerce\Api\WooCommerceApi;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
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

    private Collection|null $_wooCommerceCustomers = null;
    private Collection|null $_wooCommerceSubscriptions = null;
    private Collection|null $_wooCommerceUserMemberships = null;

    private Collection|null $_members = null;

    private OutputInterface|null $output = null;

    public function __construct(
        WooCommerceApi $wooCommerceApi
    )
    {
        $this->wooCommerceApi = $wooCommerceApi;
    }

    public function setOutput(OutputInterface|null $output): void
    {
        $this->output = $output;
    }

    /**
     * @throws ApiCallFailed
     */
    public function wooCommerceCustomers(): Collection
    {
        if (is_null($this->_wooCommerceCustomers)) {
            Log::info("Fetching WooCommerce Customers");
            $this->_wooCommerceCustomers = $this->wooCommerceApi->customers->list($this->apiProgress("WooCommerce Customers"));
            Log::info("Fetched WooCommerce Customers");
        }

        return $this->_wooCommerceCustomers;
    }

    /**
     * @throws ApiCallFailed
     */
    public function wooCommerceSubscriptions(): Collection
    {
        if (is_null($this->_wooCommerceSubscriptions)) {
            Log::info("Fetching WooCommerce Subscriptions");
            $this->_wooCommerceSubscriptions = $this->wooCommerceApi->subscriptions->list($this->apiProgress("WooCommerce Subscriptions"));
            Log::info("Fetched WooCommerce Subscriptions");
        }

        return $this->_wooCommerceSubscriptions;
    }

    /**
     * @throws ApiCallFailed
     */
    public function wooCommerceUserMemberships(): Collection
    {
        if (is_null($this->_wooCommerceUserMemberships)) {
            Log::info("Fetching WooCommerce User Memberships");
            $this->_wooCommerceUserMemberships = $this->wooCommerceApi->members->list($this->apiProgress("WooCommerce User Memberships"));
            Log::info("Fetched WooCommerce User Memberships");
        }

        return $this->_wooCommerceUserMemberships;
    }

    /**
     * @throws ApiCallFailed
     */
    public function members()
    {
        if (is_null($this->_members)) {
            Log::info("Fetching memberships");
            $subscriptions = $this->wooCommerceSubscriptions();

            $this->_members = $this->wooCommerceCustomers()->map(function ($customer) use ($subscriptions) {
                $isMember = $subscriptions
                    ->where('customer_id', $customer['id'])
                    ->whereIn('status', ['active', 'pending-cancel'])
                    ->isNotEmpty();

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

                return [
                    'id' => $customer['id'],
                    'first_name' => $customer['first_name'],
                    'last_name' => $customer['last_name'],
                    'email' => $emails,
                    'is_member' => $isMember,
                    'subscriptions' => $subscriptionMap,
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
                        'subscriptions' => collect(),
                        'cards' => is_null($member->card) ? collect() : collect([$member->card]),
                        'slack_id' => $member->slack_id,
                        'system' => self::SYSTEM_PAYPAL,
                    ];
                }));
            Log::info("Fetched memberships");
        }
        return $this->_members;
    }

    private function getMetaValue($meta_data, $key)
    {
        $meta_entry = $meta_data->where('key', $key)->first();
        return is_null($meta_entry) ? null : ($meta_entry['value'] ?: null);
    }
}
