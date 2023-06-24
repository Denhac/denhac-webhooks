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

        Log::info("Waiver Forever signature header: " . $signatureHeader);
        Log::info("Waiver Forever payload: " . $request->getContent());

        return true;
    }
}
