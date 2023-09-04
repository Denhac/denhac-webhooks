<?php

namespace App\External\QuickBooks\Webhooks;

use App\External\QuickBooks\QuickBooksAuthSettings;
use Illuminate\Support\Facades\Log;
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

        if (! array_key_exists('eventNotifications', $payload)) {
            return;
        }

        $eventNotifications = $payload['eventNotifications'];

        $ourRealmId = QuickBooksAuthSettings::getRealmId();
        if (is_null($ourRealmId)) {
            return;  // If we have not authed with a server, we will ignore all events so exit early
        }

        foreach ($eventNotifications as $notification) {
            if (! array_key_exists('realmId', $notification) ||
                $notification['realmId'] != $ourRealmId) {
                continue;
            }

            if (! array_key_exists('dataChangeEvent', $notification) ||
                ! array_key_exists('entities', $notification['dataChangeEvent'])) {
                continue;
            }

            $entities = $notification['dataChangeEvent']['entities'];

            foreach ($entities as $entity) {
                Log::info('QuickBooks entity update');
                Log::info(print_r($entity, true));
            }
        }
    }
}
