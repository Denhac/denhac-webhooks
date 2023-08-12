<?php

namespace App\Issues\Checkers;


use App\Card;
use App\Issues\IssueData;
use App\Issues\Types\InternalConsistency\ActiveCardNotInCustomerProfile;
use App\Issues\Types\InternalConsistency\CardInPossessionOfMultipleCustomers;
use App\Issues\Types\InternalConsistency\CustomerHasUnknownCard;
use App\Issues\Types\InternalConsistency\CustomerStillInPossessionOfCardNotInCustomerProfile;
use App\Issues\Types\InternalConsistency\MemberCardIsNotActive;
use App\Issues\Types\InternalConsistency\CardIsActivateWhenItShouldNotBe;

class InternalConsistencyCardIssues implements IssueCheck
{
    use IssueCheckTrait;

    private IssueData $issueData;

    public function __construct(IssueData $issueData)
    {
        $this->issueData = $issueData;
    }

    public function generateIssues(): void
    {
        $cards = Card::all();

        $members = $this->issueData->members();

        $members->each(function ($member) use ($cards) {
            if ($member['system'] == IssueData::SYSTEM_PAYPAL) {
                // We don't update the cards database for paypal members
                return;
            }

            $cardsForMember = $cards
                ->where('woo_customer_id', $member['id']);

            // $member['cards'] is the list of cards in WooCommerce
            $member['cards']->each(function ($memberCard) use ($member, $cardsForMember) {
                if (!$cardsForMember->contains('number', $memberCard)) {
                    $this->issues->add(new CustomerHasUnknownCard($member, $memberCard));

                    return;
                }

                /** @var Card $card */
                $card = $cardsForMember->where('number', $memberCard)->first();

                $shouldHaveActiveCard = $member['is_member'] && $member['has_signed_waiver'];

                if ($shouldHaveActiveCard && !$card->active) {
                    $this->issues->add(new MemberCardIsNotActive($member, $memberCard));
                }

                if (!$shouldHaveActiveCard && $card->active) {
                    $this->issues->add(new CardIsActivateWhenItShouldNotBe($member, $memberCard));
                }
            });

            $cardsForMember->each(function ($cardForMember) use ($member) {
                /** @var Card $cardForMember */
                if ($member['cards']->contains($cardForMember->number)) { // WordPress user has this card
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
            ->filter(fn($card) => $card->member_has_card)
            ->groupBy(fn($card) => $card->number)
            ->filter(fn($value) => $value->count() > 1)
            ->each(function ($cards, $cardNum) {
                $uniqueCustomers = $cards
                    ->map(fn($card) => $card->woo_customer_id)
                    ->unique()
                    ->implode(', ');
                $numEntries = $cards->count();

                $this->issues->add(new CardInPossessionOfMultipleCustomers($cardNum, $numEntries, $uniqueCustomers));
            });
    }
}
