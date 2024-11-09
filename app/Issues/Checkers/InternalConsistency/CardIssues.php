<?php

namespace App\Issues\Checkers\InternalConsistency;

use App\DataCache\AggregateCustomerData;
use App\DataCache\MemberData;
use App\Issues\Checkers\IssueCheck;
use App\Issues\Checkers\IssueCheckTrait;
use App\Issues\Types\InternalConsistency\ActiveCardNotInCustomerProfile;
use App\Issues\Types\InternalConsistency\CardInPossessionOfMultipleCustomers;
use App\Issues\Types\InternalConsistency\CardIsActivateWhenItShouldNotBe;
use App\Issues\Types\InternalConsistency\CustomerHasUnknownCard;
use App\Issues\Types\InternalConsistency\CustomerStillInPossessionOfCardNotInCustomerProfile;
use App\Issues\Types\InternalConsistency\MemberCardIsNotActive;
use App\Models\Card;
use Illuminate\Support\Collection;

class CardIssues implements IssueCheck
{
    use IssueCheckTrait;

    public function __construct(
        private readonly AggregateCustomerData $aggregateCustomerData
    ) {}

    public function generateIssues(): void
    {
        $cards = Card::all();

        /** @var Collection<MemberData> $members */
        $members = $this->aggregateCustomerData->get();

        $members->each(function ($member) use ($cards) {
            /** @var MemberData $member */
            $cardsForMember = $cards
                ->where('customer_id', $member->id);

            // $member['cards'] is the list of cards in WooCommerce
            $member->cards->each(function ($memberCard) use ($member, $cardsForMember) {
                if (! $cardsForMember->contains('number', $memberCard)) {
                    $this->issues->add(new CustomerHasUnknownCard($member, $memberCard));

                    return;
                }

                /** @var Card $card */
                $card = $cardsForMember->where('number', $memberCard)->first();

                $shouldHaveActiveCard = $member->isMember && $member->hasSignedWaiver;

                if ($shouldHaveActiveCard && ! $card->active) {
                    $this->issues->add(new MemberCardIsNotActive($member, $memberCard));
                }

                if (! $shouldHaveActiveCard && $card->active) {
                    $this->issues->add(new CardIsActivateWhenItShouldNotBe($member, $memberCard));
                }
            });

            $cardsForMember->each(function ($cardForMember) use ($member) {
                /** @var Card $cardForMember */
                if ($member->cards->contains($cardForMember->number)) { // WordPress user has this card
                    return;
                }

                if ($cardForMember->active) {
                    $this->issues->add(new ActiveCardNotInCustomerProfile($member, $cardForMember->number));
                }

                if ($cardForMember->member_has_card) {
                    $this->issues->add(new CustomerStillInPossessionOfCardNotInCustomerProfile($member, $cardForMember->number));
                }
            });
        });

        $cards
            ->filter(fn ($card) => $card->member_has_card)
            ->groupBy(fn ($card) => $card->number)
            ->filter(fn ($value) => $value->count() > 1)
            ->each(function ($cards, $cardNum) {
                $uniqueCustomers = $cards
                    ->map(fn ($card) => $card->customer_id)
                    ->unique();
                $numEntries = $cards->count();

                $this->issues->add(new CardInPossessionOfMultipleCustomers($cardNum, $numEntries, $uniqueCustomers));
            });
    }
}
