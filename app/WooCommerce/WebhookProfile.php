<?php

namespace App\WooCommerce;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\WebhookClient\WebhookProfile\WebhookProfile as BaseWebhookProfile;

class WebhookProfile implements BaseWebhookProfile
{
    public function shouldProcess(Request $request): bool
    {
        if (Str::startsWith($request->getContent(), 'webhook_id=')) {
            // This is a test ping, we don't care about it
            return false;
        }

        return true;
    }
}
