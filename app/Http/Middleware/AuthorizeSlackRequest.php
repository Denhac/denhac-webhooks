<?php

namespace App\Http\Middleware;

use App\FeatureFlags;
use App\Slack\ValidatesSlack;
use Closure;
use Illuminate\Http\Request;
use YlsIdeas\FeatureFlags\Facades\Features;

class AuthorizeSlackRequest
{
    use ValidatesSlack;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $secret = config('denhac.slack.spacebot_api_signing_secret');
        if (! $this->isSignatureValid($request, $secret)) {
            return response()->json([
                'response_type' => 'ephemeral',
                'text' => "The signature from slack didn't match the computed value. Did our signing secret change?",
            ]);
        }

        if(Features::accessible(FeatureFlags::SLACK_CHECK_CLIENT_CERTIFICATE)) {
            if (!$this->isCertificateValid($request)) {
                return response()->json([
                    'response_type' => 'ephemeral',
                    'text' => "The certificate from slack didn't match the computed value. Did the certificate expire?",
                ]);
            }
        }

        return $next($request);
    }
}
