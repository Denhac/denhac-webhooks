<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Customer;
use App\Models\UserMembership;
use App\Models\Waiver;
use Illuminate\Http\Request;

class AllCardsController extends Controller
{
    private const string DENHAC_ACCESS = 'denhac';

    private const string SERVER_ROOM_ACCESS = 'Server Room';

    private const string CAN_OPEN_HOUSE_UDF = 'dh_can_open_house';

    private const string COMPANY_DENHAC = 'DenHac'; // This is how it exists in the card access system

    public function __invoke(Request $request)
    {
        return Customer::with(['cards', 'memberships'])
            ->withExists(['waivers as has_membership_waiver' => fn ($query) => $query
                ->where('template_id', Waiver::getValidMembershipWaiverId()),
            ])
            ->paginate(100)
            ->through(function ($customer) {
                /** @var Customer $customer */
                $shouldHaveAccess = $customer->member && $customer->has_membership_waiver;

                $cards = $customer->cards
                    ->filter(fn ($card) => $card->member_has_card)
                    ->map(function ($card) use ($customer, $shouldHaveAccess) {
                        /** @var Card $card */

                        $access = [];
                        if ($shouldHaveAccess) {
                            $access[] = self::DENHAC_ACCESS;

                            if ($customer->hasMembership(UserMembership::SERVER_ROOM_ACCESS)) {
                                $access[] = self::SERVER_ROOM_ACCESS;
                            }
                        }

                        return [
                            'card_num' => $card->number,
                            'access' => $access,
                            'we_think_active' => $card->active,
                        ];
                    })
                    ->all();

                $extra = [];
                if ($customer->isABoardMember() || $customer->isAManager()) {
                    $extra[] = self::CAN_OPEN_HOUSE_UDF;
                }

                return [
                    'id' => $customer->id,
                    'first_name' => $customer->first_name,
                    'last_name' => $customer->last_name,
                    'company' => self::COMPANY_DENHAC,
                    'cards' => array_values($cards),
                    'extra' => $extra,
                ];
            });
    }
}
