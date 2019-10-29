<?php

namespace App\WooCommerce;

use Illuminate\Http\Request;
use Spatie\WebhookClient\WebhookConfig;

class WebhookCall extends \Spatie\WebhookClient\Models\WebhookCall
{
    public static function storeWebhook(WebhookConfig $config, Request $request): \Spatie\WebhookClient\Models\WebhookCall
    {
        return self::create([
            'name' => $config->name,
            'payload' => $request->input(),
            'topic' => $request->header("X-WC-Webhook-Topic")
        ]);
    }
}
