<?php

namespace App\Http\Requests;

use DateTime;
use Illuminate\Contracts\Validation\ValidatesWhenResolved;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SlackSlashCommandRequest extends Request implements ValidatesWhenResolved
{
    public function validateResolved()
    {
        $timestamp = $this->header('x-slack-request-timestamp');
        $sentSignature = $this->header('x-slack-signature');
        $content = $this->content;

        $nowTimestamp = (new DateTime())->getTimestamp();
        if (abs($nowTimestamp - $timestamp) > 60 * 5) {
            throw new ValidationException(null, response()->json([
                'response_type' => 'ephemeral',
                'text' => 'Replay attacks are bad! Or we screwed up the time zone maybe?',
            ]));
        }

        $sig_base = "v0:$timestamp:$content";
        $secret = config('denhac.slack.api_signing_secret');
        $computedSignature = 'v0='.hash_hmac('sha256', $sig_base, $secret);

        if ($computedSignature != $sentSignature) {
            throw new ValidationException(null, response()->json([
                'response_type' => 'ephemeral',
                'text' => "The signature from slack didn't match the computed value. Did our signing secret change?",
            ]));
        }
    }
}
