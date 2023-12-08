<?php

namespace App\Http\Middleware;

use Symfony\Component\HttpFoundation\Response;
use App\External\Slack\ValidatesSlack;
use Closure;
use Illuminate\Http\Request;

class AuthorizeSlackRequest
{
    use ValidatesSlack;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('denhac.slack.spacebot_api_signing_secret');
        if (! $this->isSignatureValid($request, $secret)) {
            return response()->json([
                'response_type' => 'ephemeral',
                'text' => "The signature from slack didn't match the computed value. Did our signing secret change?",
            ]);
        }

        if (! $this->isCertificateValid($request)) {
            return response()->json([
                'response_type' => 'ephemeral',
                'text' => "The certificate from slack didn't match the computed value. Did the certificate expire?",
            ]);
        }

        $type = $request->get('type');

        if ($type == 'url_verification') {
            $challenge = $request->get('challenge');

            return response($challenge);
        }

        return $next($request);
    }
}
