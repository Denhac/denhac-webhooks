<?php

namespace App\External\WaiverForever;


use App\StorableEvents\WaiverAccepted;
use Spatie\WebhookClient\Models\WebhookCall;

class ProcessWebhookJob extends \Spatie\WebhookClient\ProcessWebhookJob
{
    public function __construct(WebhookCall $webhookCall)
    {
        parent::__construct($webhookCall);
        $this->onQueue('webhooks');
    }

    public function handle()
    {
        $payload = $this->webhookCall->payload;

        event(new WaiverAccepted($payload));
    }
}
