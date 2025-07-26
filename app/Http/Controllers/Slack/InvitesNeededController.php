<?php

namespace App\Http\Controllers\Slack;

use App\External\Slack\Channels;
use App\External\Slack\MembershipType;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\UserMembership;
use Illuminate\Http\Request;

class InvitesNeededController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $customers = Customer::with('memberships')
            ->whereNull('slack_id')
            ->whereRelation('memberships', 'plan_id', UserMembership::MEMBERSHIP_FULL_MEMBER)
            ->get();

        return $customers
            ->map(function ($customer) {
                /** @var Customer $customer */

                /** @var UserMembership $fullMemberPlan */
                $fullMemberPlan = $customer->memberships->where('plan_id', UserMembership::MEMBERSHIP_FULL_MEMBER)->first();

                if ($fullMemberPlan->status == 'active') {
                    $channels = [Channels::GENERAL, Channels::PUBLIC, Channels::RANDOM];
                    $membership_type = MembershipType::FULL_USER;
                } else if ($fullMemberPlan->status != 'paused') {
                    // Everything below here expects a paused membership. If the user membership is something other than
                    // paused or active, we don't want to invite them into our Slack workspace.
                    return null;
                } else if (! $customer->id_checked) {
                    $channels = [Channels::NEED_ID_CHECK];
                    $membership_type = MembershipType::SINGLE_CHANNEL_GUEST;
                } else if (! $customer->member) {
                    $channels = [Channels::PUBLIC];
                    $membership_type = MembershipType::SINGLE_CHANNEL_GUEST;
                } else {
                    // They have a plan that's not active but have had their id checked and are a member. This is not
                    // a situation we expect to find ourselves in, so we should maybe report on it at some point, but
                    // the issue checkers should already catch this invalid configuration.
                    return null;
                }


                return [
                    'channels' => $channels,
                    'email' => $customer->email,
                    'type' => $membership_type,
                ];
            })
            ->filter()
            ->values();
    }
}
