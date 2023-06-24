<?php

namespace App\WaiverForever;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator as SignatureValidatorBase;
use Spatie\WebhookClient\WebhookConfig;

class SignatureValidator implements SignatureValidatorBase
{
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        $signatureHeader = $request->header($config->signatureHeaderName);
        $payload = $request->getContent();
        $secret = $config->signingSecret;

        $parsedResult = [];
        parse_str(str_replace(',', '&', $signatureHeader), $parsedResult);

        if (!array_key_exists('t', $parsedResult)) {
            Log::info("WaiverForever webhook doesn't have timestamp");
            return false;
        }

        if (!array_key_exists('signature', $parsedResult)) {
            Log::info("WaiverForever webhook doesn't have signature");
            return false;
        }

        $timestamp = $parsedResult['t'];
        $expectedSignature = $parsedResult['signature'];
        $signed_payload = "$timestamp,$payload,$secret";
        $actualSignature = hash('sha256', $signed_payload);

        return $expectedSignature === $actualSignature;
    }
}
