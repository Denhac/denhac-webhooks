<?php

namespace App\External\QuickBooks\Webhooks;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator as SignatureValidatorBase;
use Spatie\WebhookClient\WebhookConfig;

class SignatureValidator implements SignatureValidatorBase
{
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        $expectedSignature = $request->header($config->signatureHeaderName);
        $payload = $request->getContent();
        $secret = $config->signingSecret;

        $actualSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $actualSignature);
    }
}
