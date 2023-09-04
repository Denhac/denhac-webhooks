<?php

namespace App\External\Slack;

use DateTime;
use Illuminate\Http\Request;
use Spatie\SslCertificate\SslCertificate;

trait ValidatesSlack
{
    public function isSignatureValid(Request $request, string $signingSecret): bool
    {
        $timestamp = $request->header('x-slack-request-timestamp');
        $sentSignature = $request->header('x-slack-signature');
        $content = $request->getContent();

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

    public function isCertificateValid(Request $request): bool
    {
        $clientVerify = $request->server('X_CLIENT_VERIFY');
        $clientCertificateDataEncoded = $request->server('X_CLIENT_CERTIFICATE');

        if ($clientVerify != 'SUCCESS') {
            return false;
        }

        $clientCertificateData = urldecode($clientCertificateDataEncoded);
        $clientCertificate = SslCertificate::createFromString($clientCertificateData);

        if (! $clientCertificate->isValid('platform-tls-client.slack.com')) {
            return false;
        }

        return true;
    }
}
