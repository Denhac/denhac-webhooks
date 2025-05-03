<?php

namespace App\External\Stripe;

use App\StorableEvents\Stripe\IssuingAuthorization;
use Spatie\WebhookClient\Models\WebhookCall;

class ProcessWebhookJob extends \Spatie\WebhookClient\Jobs\ProcessWebhookJob
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
            case 'issuing_authorization.created':
                event(new IssuingAuthorization($payload));
//            Not Sure what to do to update an authorization if its already been created
//            Would we just add it to the db as another event and handle it somewhere else?
//            case 'issuing_authorization.updated':
//                event(new IssuingAuthorization($payload));
        }


    }
}
