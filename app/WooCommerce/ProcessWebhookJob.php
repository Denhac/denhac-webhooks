<?php

namespace App\WooCommerce;


use App\StorableEvents\CustomerCreated;
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
}
