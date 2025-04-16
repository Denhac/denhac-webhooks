<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Customer;
use App\Models\UserMembership;
use Illuminate\Http\Request;

class AllCardsController extends Controller
{
    private const DENHAC_ACCESS = 'denhac';

    private const SERVER_ROOM_ACCESS = 'Server Room';

    private const CAN_OPEN_HOUSE_UDF = 'dh_can_open_house';

    public function __invoke(Request $request)
    {
        return Customer::with(['cards', 'memberships'])
            ->paginate(100)
            ->through(function ($customer) {
                /** @var Customer $customer */
                $cards = $customer->cards
                    ->filter(fn ($card) => $card->member_has_card)
                    ->map(function ($card) use ($customer) {
                        /** @var Card $card */

                        // Their card should only be active if it's already set to active, they have it, and they're a
                        // member. We already filter out cards that the member for sure does not have.
                        $activeCard = $card->active && $customer->member;

                        $access = [];
                        if ($activeCard) {
                            $access[] = self::DENHAC_ACCESS;

                            if ($customer->hasMembership(UserMembership::SERVER_ROOM_ACCESS)) {
                                $access[] = self::SERVER_ROOM_ACCESS;
                            }
                        }

                        return [
                            'card_num' => $card->number,
                            'access' => $access,
                        ];
                    })->all();

                $extra = [];
                if ($customer->isABoardMember() || $customer->isAManager()) {
                    $extra[] = self::CAN_OPEN_HOUSE_UDF;
                }

                return [
                    'id' => $customer->id,
                    'first_name' => $customer->first_name,
                    'last_name' => $customer->last_name,
                    'cards' => $cards,
                    'extra' => $extra,
                ];
            });
    }
}
