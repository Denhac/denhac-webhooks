<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

class HorizonSignedUrlController extends Controller
{
    public function __invoke(Request $request)
    {
        if (! $request->hasValidSignature()) {
            abort(401);
        }

        return response("success")->cookie(Cookie::create(
            'horizon',
            setting('horizon.password'),
            now()->addYears(5)  // It's annoying to generate again when you need it. Access is revoked for everyone by changing the password.
        ));
    }
}
