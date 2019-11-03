<?php

namespace App\WooCommerce;


use App\StorableEvents\CustomerCreated;
use App\StorableEvents\SubscriptionUpdated;
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

        switch ($this->webhookCall->topic) {
            case "customer.created":
                $this->customerCreated();
                return;
            case "subscription.updated":
                $this->subscriptionUpdated();
                return;
        }
    }

    private function customerCreated()
    {
        $payload = $this->webhookCall->payload;

        $wooId = $payload["id"];
        $email = $payload["email"];
        $username = $payload["username"];

        event(new CustomerCreated($wooId, $email, $username));
    }

    private function subscriptionUpdated()
    {
        $payload = $this->webhookCall->payload;

        $wooId = $payload["id"];
        $customerId = $payload["customer_id"];
        $status = $payload["status"];

        event(new SubscriptionUpdated($wooId, $customerId, $status));
    }
}
