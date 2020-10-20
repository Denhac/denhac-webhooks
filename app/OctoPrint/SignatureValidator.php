<?php

namespace App\OctoPrint;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator as SignatureValidatorBase;
use Spatie\WebhookClient\WebhookConfig;

class SignatureValidator implements SignatureValidatorBase
{
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        if(! $request->isJson()) {
            Log::info("It's not JSON!");
            return false;
        }

        if(! $request->has('apiSecret')) {
            Log::info("Doesn't have an api secret!");
            return false;
        }

        if($request->get('apiSecret') !== $config->signingSecret) {
            Log::info("Api secret doesn't match");
            return false;
        }

        return true;
    }
}
