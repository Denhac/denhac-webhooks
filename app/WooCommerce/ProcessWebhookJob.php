<?php

namespace App\WooCommerce;

use App\Aggregates\CapabilityAggregate;
use App\Aggregates\MembershipAggregate;
use Illuminate\Support\Facades\Log;
use Spatie\WebhookClient\Models\WebhookCall;

/**
 * Class ProcessWebhookJob.
 * @property WebhookCall $webhookCall
 */
class ProcessWebhookJob extends \Spatie\WebhookClient\ProcessWebhookJob
{
    public function __construct(WebhookCall $webhookCall)
    {
        parent::__construct($webhookCall);
        $this->onQueue("webhooks");
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
            case 'action.wc_denhac_capabilities_updated':
                CapabilityAggregate::make($payload["arg"]['customer_id'])
                    ->updateCapabilities($payload["arg"]['capabilities'])
                    ->persist();
        }
    }
}
