<?php

namespace App\Http\Controllers;

use App\Card;
use App\Notifications\CardAccessAllowedButNotAMemberRedAlert;
use App\Notifications\CardAccessDeniedBadDoor;
use App\Notifications\CardAccessDeniedBecauseNotAMember;
use App\Notifications\CardAccessDeniedButWereWorkingOnIt;
use App\Notifications\CardAccessDeniedUnderConstruction;
use App\Notifications\MemberBadgedIn;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class ApiEventsController extends Controller
{
    /**
     * @var string
     */
    private $slackCardScanRoute;

    public function __construct()
    {
        $this->slackCardScanRoute = config('denhac.notifications.slack.card_scan_channel_webhook');
    }

    public function cardScanned(Request $request)
    {
        $cardNumber = $request->get('card_num');
        /** @var Collection $cards */
        $cards = Card::where('number', $cardNumber)->get();

        if($cards->count() > 1) {
            // TODO What do we even do here? Should we still notify of scan in?
            report(new \Exception("More than one card listed with card {$cardNumber} on card scan"));
            return;
        }

        $accessAllowed = $request->get('access_allowed');
        $device = $request->get('device');

        /** @var Card $card */
        $card = $cards->first();
        if( is_null($card)) {
            if($accessAllowed) {
                $this->notifyBadgeInToSlack($request);
            }
            return;
        }

        $customer = $card->customer;

        if( is_null($customer) ) {
            if($accessAllowed) {
                $this->notifyBadgeInToSlack($request);
            }
            return;
        }

        if($customer->member) {
            if($accessAllowed) {
                $this->notifyBadgeInToSlack($request);
            } else {
                // They weren't given access
                if($device == 0 || $device == 1) { // Front and Side door
                    $notification = new CardAccessDeniedButWereWorkingOnIt(
                        $request->json("first_name"),
                        $request->json("last_name"),
                        $request->json("card_num"),
                        $request->json("scan_time")
                    );

                    Notification::route('slack', $this->slackCardScanRoute)
                        ->route('mail', $customer->email)
                        ->notify($notification);
                } else if($device == 2) { // Back door
                    $notification = new CardAccessDeniedBadDoor();

                    Notification::route('mail', $customer->email)
                        ->notify($notification);
                } else if($device == 3) { // denhac door
                    $notification = new CardAccessDeniedUnderConstruction();

                    Notification::route('mail', $customer->email)
                        ->notify($notification);
                }
            }
        } else {
            if($accessAllowed) {
                $notification = new CardAccessAllowedButNotAMemberRedAlert(
                    $request->json("first_name"),
                    $request->json("last_name"),
                    $request->json("card_num"),
                    $request->json("scan_time")
                );

                Notification::route('slack', $this->slackCardScanRoute)
                    ->route('mail', config('denhac.access_email'))
                    ->notify($notification);
            } else {
                $notification = new CardAccessDeniedBecauseNotAMember();

                Notification::route('mail', $customer->email)
                    ->notify($notification);
            }
        }
    }

    /**
     * @param Request $request
     */
    private function notifyBadgeInToSlack(Request $request): void
    {
        Notification::route('slack', $this->slackCardScanRoute)
            ->notify(new MemberBadgedIn(
                $request->json("first_name"),
                $request->json("last_name"),
                $request->json("scan_time")
            ));
    }
}
