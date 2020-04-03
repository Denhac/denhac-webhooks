<?php

namespace App\Http\Requests;

use App\Slack\ValidatesSlackSignature;
use Illuminate\Contracts\Validation\ValidatesWhenResolved;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SlackEventRequest extends Request implements ValidatesWhenResolved
{
    use ValidatesSlackSignature;

    /**
     * @throws ValidationException
     */
    public function validateResolved()
    {
        $secret = config('denhac.slack.management_api_signing_secret');
        if(! $this->isSlackSignatureValid($this, $secret)) {
            throw new ValidationException(null, response()->json());
        }
    }
}
