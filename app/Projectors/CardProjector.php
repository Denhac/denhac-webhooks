<?php

namespace App\Projectors;

use App\Models\Card;
use App\StorableEvents\AccessCards\CardActivated;
use App\StorableEvents\AccessCards\CardAdded;
use App\StorableEvents\AccessCards\CardDeactivated;
use App\StorableEvents\AccessCards\CardRemoved;
use App\StorableEvents\WooCommerce\CustomerDeleted;
use App\StorableEvents\WooCommerce\UserMembershipImported;
use Illuminate\Database\Eloquent\Collection;
use Spatie\EventSourcing\EventHandlers\Projectors\Projector;

class CardProjector extends Projector
{
    public function onStartingEventReplay()
    {
        Card::truncate();
    }

    public function onCardAdded(CardAdded $event)
    {
        $cardNumber = ltrim($event->cardNumber, '0');

        /** @var Card $card */
        $card = Card::where('number', $cardNumber)
            ->where('customer_id', $event->wooCustomerId)
            ->first();

        if (is_null($card)) {
            Card::create([
                'number' => $cardNumber,
                'customer_id' => $event->wooCustomerId,
                'active' => false,
                'member_has_card' => true,
            ]);
        } else {
            $card->member_has_card = true;
            $card->save();
        }
    }

    public function onCardActivated(CardActivated $event)
    {
        /** @var Card $card */
        $card = Card::where('number', ltrim($event->cardNumber, '0'))
            ->where('customer_id', $event->wooCustomerId)
            ->first();

        if (is_null($card)) {
            return;
        }

        $card->active = true;

        $card->save();
    }

    public function onCardRemoved(CardRemoved $event)
    {
        /** @var Card $card */
        $card = Card::where('number', ltrim($event->cardNumber, '0'))
            ->where('customer_id', $event->wooCustomerId)
            ->first();

        if (is_null($card)) {
            return;
        }

        $card->member_has_card = false;

        $card->save();
    }

    public function onCardDeactivated(CardDeactivated $event)
    {
        /** @var Card $card */
        $card = Card::where('number', ltrim($event->cardNumber, '0'))
            ->where('customer_id', $event->wooCustomerId)
            ->first();

        if (is_null($card)) {
            return;
        }

        $card->active = false;

        $card->save();
    }

    public function onUserMembershipImported(UserMembershipImported $event)
    {
        if ($event->membership['status'] != 'active') {
            return;
        }

        /** @var Collection $cards */
        $cards = Card::where('customer_id', $event->membership['customer_id'])->get();

        $cards->each(function ($card) {
            /* @var Card $card */
            $card->active = true;

            $card->save();
        });
    }

    public function onCustomerDeleted(CustomerDeleted $event)
    {
        $cards = Card::where('customer_id', $event->customerId)->get();
        foreach ($cards as $card) {
            /* @var Card $card */
            $card->delete();
        }
    }
}
