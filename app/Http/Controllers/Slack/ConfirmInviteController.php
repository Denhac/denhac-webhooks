<?php

namespace App\Http\Controllers\Slack;

use App\External\WooCommerce\Api\WooCommerceApi;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConfirmInviteController extends Controller
{
    public function __invoke(Request $request, WooCommerceApi $api)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'slack_id' => 'required',
        ]);

        $email = $validated['email'];
        $slack_id = $validated['slack_id'];

        /** @var Customer $customer */
        $customer = Customer::where('email', $email)->first();

        if(is_null($customer)) {
            return response()->json([
                'message' => 'Could not find customer by that email address"',
            ], Response::HTTP_NOT_FOUND);
        }

        if(! is_null($customer->slack_id)) {
            return response()->json([
                'message' => 'Customer already has a Slack ID',
            ], Response::HTTP_CONFLICT);
        }

        $api->customers
            ->update($customer->id, [
                'meta_data' => [
                    [
                        'key' => 'access_slack_id',
                        'value' => $slack_id,
                    ],
                ],
            ]);

        return response()->json();
    }
}
