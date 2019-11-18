<?php

namespace App\Jobs;


use App\Slack\SlackApi;
use App\WooCommerce\Api\ApiCallFailed;
use App\WooCommerce\Api\WooCommerceApi;

trait MakeCustomerMemberInSlackMixin
{
    protected $wooCustomerId;

    /**
     * @param WooCommerceApi $wooCommerceApi
     * @param SlackApi $slackApi
     * @throws ApiCallFailed
     */
    public function handle(WooCommerceApi $wooCommerceApi, SlackApi $slackApi)
    {
        $customer = $wooCommerceApi->customers->get($this->wooCustomerId);
        $customer_email = $wooCommerceApi["email"];

        $slack_id = collect($customer["meta_data"])
                ->firstWhere("key", "access_slack_id")["value"] ?? null;

        if(!is_null($slack_id)) {
            $this->handleExistingMember($slackApi, $slack_id);
        } else {
            $emails = [
                $customer_email => "ultra_restricted",
            ];
            $channels = $slackApi->channelIdsByName(["general", "public", "random"]);
            $slackApi->users_admin_inviteBulk($customer_email, $channels);
            // TODO Report exception if the overall request isn't okay or per user isn't okay

            $slackObject = $slackApi->users_lookupByEmail($customer_email);
            if(is_null($slackObject)) {
                throw new \Exception("Slack user was null, unsure if invite worked");
            }
            $slack_id = $slackObject["id"];

            $wooCommerceApi->customers->update($this->wooCustomerId, [
                "meta_data" => [
                    [
                        "key" => "access_slack_id",
                        "value" => $slack_id,
                    ]
                ]
            ]);
        }
    }

    // Handle an existing member
    protected abstract function handleExistingMember(SlackApi $slackApi, $slack_id);

    // What membership type should we use for the invite?
    protected abstract function membershipType();

    // What channels should they be part of
    protected abstract function channelIds(SlackApi $slackApi);
}
