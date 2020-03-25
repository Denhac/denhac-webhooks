<?php

namespace App\Http\Controllers;

use App\Notifications\MemberBadgedIn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class ApiEventsController extends Controller
{
    public function cardScanned(Request $request)
    {
        $slackRoute = config('denhac.notifications.slack.card_scan_channel_webhook');
        Notification::route('slack', $slackRoute)
            ->notify(new MemberBadgedIn(
                $request->json("first_name"),
                $request->json("last_name"),
                $request->json("scan_time")
            ));
    }
}
