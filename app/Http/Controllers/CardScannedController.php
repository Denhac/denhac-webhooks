<?php

namespace App\Http\Controllers;

use App\External\WinDSX\Door;
use App\Models\Card;
use App\Models\Waiver;
use App\Notifications\CardAccessAllowedButNotAMemberRedAlert;
use App\Notifications\CardAccessDeniedBadDoor;
use App\Notifications\CardAccessDeniedBecauseNotAMember;
use App\Notifications\CardAccessDeniedButWereWorkingOnIt;
use App\Notifications\CardAccessDeniedNoWaiver;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class CardScannedController extends Controller
{
    public function __invoke(Request $request)
    {
        Log::info($request->getContent());
        $cardNumber = $request->get('card_num');
        /** @var Collection $cards */
        $cards = Card::where('number', $cardNumber)
            ->where('member_has_card', true)
            ->get();

        if ($cards->count() > 1) {
            report(new \Exception("More than one card listed with card {$cardNumber} on card scan"));

            return;
        }

        $accessAllowed = $request->get('access_allowed');
        $device = $request->get('device');

        /** @var Card $card */
        $card = $cards->first();
        if (is_null($card)) {
            Log::info("Couldn't find that card in our database");

            return;
        }

        $updatedAt = $card->updated_at;
        $updatedAt->tz('America/Denver');
        $scanTime = Carbon::parse($request->json('scan_time'), 'America/Denver');
        $customer = $card->customer;

        if (is_null($customer)) {
            Log::info("Couldn't find that customer for that card");

            return;
        }

        if ($customer->member) {
            Log::info("They're a member!");
            if (! $accessAllowed) {
                Log::info('No access though.');
                // They weren't given access

                $door = Door::byDSXDeviceId($device);

                if (is_null($door)) {
                    Log::info("We don't know about this door {$device}");
                    // We don't know about this door. Do nothing
                    return;
                } elseif ($door->membersCanBadgeIn) {
                    if (! $customer->hasSignedMembershipWaiver()) {
                        $notification = new CardAccessDeniedNoWaiver($customer);

                        Notification::route('mail', $customer->email)
                            ->notify($notification);

                        return;
                    }

                    /** @var Waiver $waiver */
                    $waiver = $customer->getMembershipWaiver();

                    if ($scanTime->subMinutes(10) < $waiver->updated_at) {
                        return;  // They just signed the waiver, give it a bit to activate their card before emailing everyone
                    }

                    if ($scanTime->subMinutes(10) < $updatedAt) {
                        // We created this card less than 10 minutes ago. It might not even be active yet.
                        return;
                    }

                    $notification = new CardAccessDeniedButWereWorkingOnIt(
                        $request->json('first_name'),
                        $request->json('last_name'),
                        $request->json('card_num'),
                        $request->json('scan_time')
                    );

                    Notification::route('mail', $customer->email)
                        ->notify($notification);
                } else {
                    $notification = new CardAccessDeniedBadDoor();

                    Notification::route('mail', $customer->email)
                        ->notify($notification);
                }
            }
        } else {
            if ($accessAllowed) {
                $notification = new CardAccessAllowedButNotAMemberRedAlert(
                    $request->json('first_name'),
                    $request->json('last_name'),
                    $request->json('card_num'),
                    $request->json('scan_time')
                );

                Notification::route('mail', config('denhac.access_email'))
                    ->notify($notification);
            } else {
                $notification = new CardAccessDeniedBecauseNotAMember();

                Notification::route('mail', $customer->email)
                    ->notify($notification);
            }
        }
    }
}
