<?php

namespace App\Http\Controllers;

use App\External\WinDSX\Door;
use App\Models\Card;
use App\Models\Customer;
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
use Psr\Log\LoggerInterface;

class CardScannedController extends Controller
{
    private readonly LoggerInterface $log;

    public function __construct()
    {
        $this->log = Log::channel('card-access');
    }

    public function __invoke(Request $request): void
    {
        $this->log->info($request->getContent());
        $cardNumber = $request->get('card_num');
        /** @var Collection $cards */
        $cards = Card::where('number', $cardNumber)
            ->where('member_has_card', true)
            ->get();

        if ($cards->count() > 1) {
            report(new \Exception("More than one card listed with card {$cardNumber} on card scan"));

            return;
        }

        /** @var Card $card */
        $card = $cards->first();
        if (is_null($card)) {
            $this->log->info("Couldn't find that card in our database");

            return;
        }

        $customer = $card->customer;

        if (is_null($customer)) {
            $this->log->info("Couldn't find that customer for that card");

            return;
        }

        if ($customer->member) {
            $this->handleMemberBadge($request, $customer, $card);
        } else {
            $this->handleNonMemberBadgeIn($request, $customer);
        }
    }

    /**
     * @param Request $request
     * @param Customer $customer
     * @param Card $card
     * @return void
     */
    public function handleMemberBadge(Request $request, Customer $customer, Card $card): void
    {
        $accessAllowed = $request->get('access_allowed');
        $device = $request->get('device');

        $scanTime = Carbon::parse($request->json('scan_time'), 'America/Denver');

        if ($accessAllowed) {
            // They're a member and access was allowed. Business as usual.
            return;
        }

        $door = Door::byDSXDeviceId($device);

        if (is_null($door)) {
            // We don't know about this door. Probably another tenant door. Do nothing.
            return;
        }

        if ($door->membersCanBadgeIn) {
            // They must have signed the waiver first
            if (! $customer->hasSignedMembershipWaiver()) {
                $this->log->info("Member access not allowed as no waiver is signed.");

                $notification = new CardAccessDeniedNoWaiver($customer);

                Notification::route('mail', $customer->email)
                    ->notify($notification);

                return;
            }

            /** @var Waiver $waiver */
            $waiver = $customer->getMembershipWaiver();

            // The system has seen the signed waiver, but the card access computer may not have updated yet.
            if ($scanTime->subMinutes(10) < $waiver->updated_at->tz('America/Denver')) {
                $this->log->info("Member access not allowed as waiver was signed recently.");

                return;
            }

            // The system recently updated this card model, so it's possible it was just issued. There are other reasons
            // the card could get updated, but most of them relate to activation/deactivation so it's a good approximation.
            if ($scanTime->subMinutes(10) < $card->updated_at->tz('America/Denver')) {
                $this->log->info("Member access not allowed as card was updated recently.");

                return;
            }

            $this->log->info("Member access not allowed for some unknown reason.");

            $notification = new CardAccessDeniedButWereWorkingOnIt(
                $request->json('first_name'),
                $request->json('last_name'),
                $request->json('card_num'),
                $request->json('scan_time')
            );

        } else {
            // This currently only happens because there's one door with badge access that leads into denhac space, but
            // kept not being safely secured and now is exit only.
            $notification = new CardAccessDeniedBadDoor;
        }

        Notification::route('mail', $customer->email)
            ->notify($notification);
    }

    /**
     * @param Request $request
     * @param Customer $customer
     * @return void
     */
    public function handleNonMemberBadgeIn(Request $request, Customer $customer): void
    {
        $accessAllowed = $request->get('access_allowed');

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
            $notification = new CardAccessDeniedBecauseNotAMember;

            Notification::route('mail', $customer->email)
                ->notify($notification);
        }
    }
}
