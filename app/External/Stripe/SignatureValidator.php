<?php

namespace App\External\Stripe;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator as SignatureValidatorBase;
use Spatie\WebhookClient\WebhookConfig;

//Set up using https://docs.stripe.com/webhooks?verify=verify-manually

class SignatureValidator
{
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        $signatureHeader = $request->header($config->signatureHeaderName);
        $payload = $request->getContent();
        $secret = $config->signingSecret;

        $parsedResult = [];
        parse_str(str_replace(',', '&', $signatureHeader), $parsedResult);

        if (! array_key_exists('t', $parsedResult)) {
            Log::info("Stripe webhook doesn't have timestamp");

            return false;
        }

        if (! array_key_exists('v1', $parsedResult)) {
            Log::info("Stripe webhook doesn't have signature");

            return false;
        }

        $timestamp = $parsedResult['t'];
        $expectedSignature = $parsedResult['signature'];
        $signed_payload = "$timestamp,'.',$payload";
        $actualSignature = hash_hmac('sha256', $signed_payload, $secret);

        return $expectedSignature === $actualSignature;
    }
}
