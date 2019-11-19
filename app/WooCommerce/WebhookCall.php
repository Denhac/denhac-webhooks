<?php

namespace App\WooCommerce;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\WebhookClient\WebhookConfig;

/**
 * Class WebhookCall
 * @package App\WooCommerce
 * @property string name
 * @property array payload
 * @property array exception
 * @property string topic
 * @property int id
 */
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
