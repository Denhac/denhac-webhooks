<?php

namespace App\External\WooCommerce;

use App\Aggregates\MembershipAggregate;
use App\Subscription;
use App\UserMembership;
use Illuminate\Support\Facades\Log;
use Spatie\WebhookClient\Models\WebhookCall;

/**
 * Class ProcessWebhookJob.
 * @property \App\External\WooCommerce\WebhookCall $webhookCall
 */
class ProcessWebhookJob extends \Spatie\WebhookClient\ProcessWebhookJob
{
    public function __construct(WebhookCall $webhookCall)
    {
        parent::__construct($webhookCall);
        $this->onQueue('webhooks');
    }

    public function handle()
    {
        if (is_null($this->webhookCall->topic)) {
            // Probably an error
            Log::error('Webhook call '.$this->webhookCall->id.' had no topic');

            return;
        }

        $payload = $this->webhookCall->payload;

        switch ($this->webhookCall->topic) {
            case 'customer.created':
                MembershipAggregate::make($payload['id'])
                    ->createCustomer($payload)
                    ->persist();
                break;
            case 'customer.updated':
                MembershipAggregate::make($payload['id'])
                    ->updateCustomer($payload)
                    ->persist();
                break;
            case 'customer.deleted':
                MembershipAggregate::make($payload['id'])
                    ->deleteCustomer($payload)
                    ->persist();
                break;
            case 'user_membership.created':
                MembershipAggregate::make($payload['customer_id'])
                    ->createUserMembership($payload)
                    ->persist();
                break;
            case 'user_membership.updated':
                if(array_key_exists('customer_id', $payload)) {
                    $customerId = $payload['customer_id'];
                } else {
                    $customerId = $this->customerIdFromUserMembershipId($payload['id']);
                }
                MembershipAggregate::make($customerId)
                    ->updateUserMembership($payload)
                    ->persist();
                break;
            case 'user_membership.deleted':
                $customerId = $this->customerIdFromUserMembershipId($payload['id']);

                if (! is_null($customerId)) {
                    MembershipAggregate::make($customerId)
                        ->deleteUserMembership($payload)
                        ->persist();
                }
                break;
            case 'subscription.created':
                MembershipAggregate::make($payload['customer_id'])
                    ->createSubscription($payload)
                    ->persist();
                break;
            case 'subscription.updated':
                MembershipAggregate::make($payload['customer_id'])
                    ->updateSubscription($payload)
                    ->persist();
                break;
            case 'subscription.deleted':
                $customerId = $this->customerIdFromSubscriptionId($payload['id']);

                if (! is_null($customerId)) {
                    MembershipAggregate::make($customerId)
                        ->deleteSubscription($payload)
                        ->persist();
                }
                break;
        }
    }

    protected function customerIdFromUserMembershipId($id): ?int
    {
        /** @var UserMembership $user_membership */
        $user_membership = UserMembership::find($id);

        if (! is_null($user_membership)) {
            return $user_membership->customer_id;
        }
        return null;
    }

    protected function customerIdFromSubscriptionId($id): ?string
    {
        /** @var Subscription $subscription */
        $subscription = Subscription::whereWooId($id)->first();

        if (! is_null($subscription)) {
            return $subscription->customer_id;
        }
        return null;
    }
}
