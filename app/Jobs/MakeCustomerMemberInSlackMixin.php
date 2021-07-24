<?php

namespace App\Jobs;

use App\Slack\SlackApi;
use App\WooCommerce\Api\ApiCallFailed;
use App\WooCommerce\Api\WooCommerceApi;

trait MakeCustomerMemberInSlackMixin
{
    /**
     * @var WooCommerceApi
     */
    protected $wooCommerceApi;

    /**
     * @var SlackApi
     */
    protected $slackApi;
    public $wooCustomerId;

    private $customerInfoFetched = false;
    private $customerEmail = null;
    private $customerSlackId = null;

    private function fetchCustomerInfo()
    {
        if (is_null($this->wooCommerceApi)) {
            $this->wooCommerceApi = app(WooCommerceApi::class);
        }

        if (is_null($this->slackApi)) {
            $this->slackApi = app(SlackApi::class);
        }

        if ($this->customerInfoFetched) {
            return;
        }

        $customer = $this->wooCommerceApi->customers->get($this->wooCustomerId);
        $this->customerEmail = $customer['email'];
        $this->customerSlackId = collect($customer['meta_data'])
                ->firstWhere('key', 'access_slack_id')['value'] ?? null;
        $this->customerInfoFetched = true;
    }

    protected function isExistingSlackUser()
    {
        $this->fetchCustomerInfo();

        return ! is_null($this->customerSlackId);
    }

    protected function inviteSingleChannelGuest($channel)
    {
        $this->inviteCustomer('ultra_restricted', $channel);
    }

    protected function inviteRegularMember($channels)
    {
        $this->inviteCustomer('regular', $channels);
    }

    protected function setSingleChannelGuest($channel)
    {
        $channel = $this->slackApi->channels($channel)[0];
        $this->slackApi->users_admin_setUltraRestricted($this->customerSlackId, $channel);
    }

    protected function setRegularMember()
    {
        $this->slackApi->users_admin_setRegular($this->customerSlackId);
    }

    private function inviteCustomer($membershipType, $channels)
    {
        $emails = [
            $this->customerEmail => $membershipType,
        ];
        $channels = $this->slackApi->channels($channels);
        $this->slackApi->users_admin_inviteBulk($emails, $channels);
        // TODO Report exception if the overall request isn't okay or per user isn't okay

        // The slack API doesn't update fast enough
        sleep(10);

        $slackObject = $this->slackApi->users->lookupByEmail($this->customerEmail);
        if (is_null($slackObject)) {
            throw new \Exception('Slack user was null, unsure if invite worked');
        }
        $this->customerSlackId = $slackObject['id'];

        $this->wooCommerceApi->customers->update($this->wooCustomerId, [
            'meta_data' => [
                [
                    'key' => 'access_slack_id',
                    'value' => $this->customerSlackId,
                ],
            ],
        ]);
    }
}
