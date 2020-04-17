<?php

namespace App\Projectors;

use App\Card;
use App\StorableEvents\CardActivated;
use App\StorableEvents\CardAdded;
use App\StorableEvents\CardDeactivated;
use App\StorableEvents\CardRemoved;
use App\StorableEvents\SubscriptionImported;
use Illuminate\Database\Eloquent\Collection;
use Spatie\EventSourcing\Projectors\Projector;
use Spatie\EventSourcing\Projectors\ProjectsEvents;

class CardProjector implements Projector
{
    use ProjectsEvents;

    public function onStartingEventReplay()
    {
        Card::truncate();
    }

    public function onCardAdded(CardAdded $event)
    {
        Card::create([
            'number' => $event->cardNumber,
            'woo_customer_id' => $event->wooCustomerId,
            'active' => false,
            'member_has_card' => true,
        ]);
    }

    public function onCardActivated(CardActivated $event)
    {
        /** @var Card $card */
        $card = Card::where('number', $event->cardNumber)
            ->where('woo_customer_id', $event->wooCustomerId)
            ->first();

        $card->active = true;

        $card->save();
    }

    public function onCardRemoved(CardRemoved $event)
    {
        /** @var Card $card */
        $card = Card::where('number', $event->cardNumber)
            ->where('woo_customer_id', $event->wooCustomerId)
            ->first();

        $card->member_has_card = false;

        $card->save();
    }

    public function onCardDeactivated(CardDeactivated $event)
    {
        /** @var Card $card */
        $card = Card::where('number', $event->cardNumber)
            ->where('woo_customer_id', $event->wooCustomerId)
            ->first();

        $card->active = false;

        $card->save();
    }

    public function onSubscriptionImported(SubscriptionImported $event)
    {
        if($event->subscription['status'] != 'active') {
            return;
        }

        /** @var Collection $cards */
        $cards = Card::where('woo_customer_id', $event->subscription['customer_id'])->get();

        $cards->each(function ($card) {
            /** @var Card $card */
            $card->active = true;

            $card->save();
        });
    }
}
