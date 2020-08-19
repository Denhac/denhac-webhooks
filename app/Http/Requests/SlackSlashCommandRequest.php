<?php

namespace App\Http\Requests;

use App\Customer;
use App\Slack\ValidatesSlackSignature;
use Illuminate\Contracts\Validation\ValidatesWhenResolved;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SlackSlashCommandRequest extends Request implements ValidatesWhenResolved
{
    use ValidatesSlackSignature;

    /**
     * @throws ValidationException
     */
    public function validateResolved()
    {
        $secret = config('denhac.slack.spacebot_api_signing_secret');
        if(! $this->isSlackSignatureValid($this, $secret)) {
            throw new ValidationException(null, response()->json([
                'response_type' => 'ephemeral',
                'text' => "The signature from slack didn't match the computed value. Did our signing secret change?",
            ]));
        }
    }

    public function customer()
    {
        $userId = $this->get('user_id');
        /** @var Customer $customer */
        $customer = Customer::whereSlackId($userId)->first();

        return $customer;
    }
}
