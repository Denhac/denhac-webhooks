<?php

namespace App\OctoPrint;


use App\Aggregates\OctoPrintAggregate;
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

        OctoPrintAggregate::make()
            ->handle($payload)
            ->persist();
    }
}
