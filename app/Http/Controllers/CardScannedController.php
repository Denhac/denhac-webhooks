<?php

namespace App\Http\Controllers;

use App\Card;
use App\Notifications\CardAccessAllowedButNotAMemberRedAlert;
use App\Notifications\CardAccessDeniedBadDoor;
use App\Notifications\CardAccessDeniedBecauseNotAMember;
use App\Notifications\CardAccessDeniedButWereWorkingOnIt;
use App\Notifications\MemberBadgedIn;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class CardScannedController extends Controller
{
    /**
     * @var string
     */
    private $slackCardScanRoute;

    public function __construct()
    {
        $this->slackCardScanRoute = config('denhac.notifications.slack.card_scan_channel_webhook');
    }

    public function __invoke(Request $request)
    {
        Log::info($request->getContent());
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
            Log::info("Couldn't find that card in our database");
            if($accessAllowed) {
                $this->notifyBadgeInToSlack($request);
            }
            return;
        }

        $customer = $card->customer;

        if( is_null($customer) ) {
            Log::info("Couldn't find that customer for that card");
            if($accessAllowed) {
                $this->notifyBadgeInToSlack($request);
            }
            return;
        }

        if($customer->member) {
            Log::info("They're a member!");
            if($accessAllowed) {
                $this->notifyBadgeInToSlack($request);
            } else {
                Log::info("No access though.");
                // They weren't given access
                if($device == 0 || $device == 1 || $device == 3) { // Front and Side door
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
