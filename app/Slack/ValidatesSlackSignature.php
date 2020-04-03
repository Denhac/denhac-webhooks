<?php

namespace App\Slack;


use DateTime;
use Illuminate\Http\Request;

trait ValidatesSlackSignature
{
    public function isSlackSignatureValid(Request $request, string $signingSecret)
    {
        $timestamp = $request->header('x-slack-request-timestamp');
        $sentSignature = $request->header('x-slack-signature');
        $content = $request->content;

        $nowTimestamp = (new DateTime())->getTimestamp();
        if (abs($nowTimestamp - $timestamp) > 60 * 5) {
            return false;
        }

        $sig_base = "v0:$timestamp:$content";
        $computedSignature = 'v0='.hash_hmac('sha256', $sig_base, $signingSecret);

        if ($computedSignature != $sentSignature) {
            return false;
        }

        return true;
    }
}
