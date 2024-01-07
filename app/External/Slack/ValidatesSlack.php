<?php

namespace App\External\Slack;

use DateTime;
use Illuminate\Http\Request;
use Spatie\SslCertificate\SslCertificate;
use Illuminate\Support\Facades\Log;

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

        if ($clientVerify != 'SUCCESS') {
            LOG::info('ValidatesSlack: Slack request had invalid X_CLIENT_VERIFY header: "'.$request->server('X_CLIENT_VERIFY').'"');
            return false;
        }

        $clientCertificateData = urldecode($clientCertificateDataEncoded);
        $clientCertificate = SslCertificate::createFromString($clientCertificateData);

        if (! $clientCertificate->isValid('platform-tls-client.slack.com')) {
            LOG::info('ValidatesSlack: Slack request failed client cerificate verification: X_CLIENT_CERTIFICATE="'.$request->server('X_CLIENT_CERTIFICATE').'"');
            return false;
        }

        return true;
    }
}
