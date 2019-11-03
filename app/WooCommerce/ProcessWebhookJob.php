<?php

namespace App\WooCommerce;


use App\Aggregates\MembershipAggregate;
use Illuminate\Support\Facades\Log;

/**
 * Class ProcessWebhookJob
 * @package App\WooCommerce
 * @property WebhookCall $webhookCall
 */
class ProcessWebhookJob extends \Spatie\WebhookClient\ProcessWebhookJob
{
    public function handle()
    {
        if(is_null($this->webhookCall->topic)) {
            // Probably an error
            Log::error("Webhook call " . $this->webhookCall->id . " had no topic");
            return;
        }

        $payload = $this->webhookCall->payload;

        switch ($this->webhookCall->topic) {
            case "customer.created":
                MembershipAggregate::make($payload["id"])
                    ->createCustomer($payload)
                    ->persist();
                break;
            case "customer.updated":
                MembershipAggregate::make($payload["id"])
                    ->updateCustomer($payload)
                    ->persist();
                break;
            case "subscription.created":
                MembershipAggregate::make($payload["customer_id"])
                    ->createSubscription($payload)
                    ->persist();
                break;
            case "subscription.updated":
                MembershipAggregate::make($payload["customer_id"])
                    ->updateSubscription($payload)
                    ->persist();
                break;
        }
    }
}
