<?php

namespace App\WooCommerce;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator as SignatureValidatorBase;
use Spatie\WebhookClient\WebhookConfig;

class SignatureValidator implements SignatureValidatorBase
{
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        if (Str::startsWith($request->getContent(), 'webhook_id=')) {
            // It's not really valid, but we're going to ignore this in the process endpoint.
            // The actual signature is empty.
            return true;
        }

        $expected = base64_encode(hash_hmac('sha256', $request->getContent(), $config->signingSecret, true));
        $actual = $request->header($config->signatureHeaderName);

        Log::info('Expected: '.$expected);
        Log::info('Content: '.$request->getContent());
        Log::info('Actual: '.$actual);

        return $expected === $actual;
    }
}
