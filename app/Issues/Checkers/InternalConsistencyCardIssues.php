<?php

namespace App\Issues\Checkers;


use App\Card;
use App\Issues\IssueData;
use Illuminate\Support\Collection;

class InternalConsistencyCardIssues implements IssueCheck
{
    private IssueData $issueData;

    public function __construct(IssueData $issueData)
    {
        $this->issueData = $issueData;
    }

    public function issueTitle(): string
    {
        return "Issue with our store's internal consistency for access cards";
    }

    public function getIssues(): Collection
    {
        $cards = Card::all();

        $issues = collect();
        $members = $this->issueData->members();

        $members->each(function ($member) use ($issues, $cards) {
            if ($member['system'] == IssueData::SYSTEM_PAYPAL) {
                // We don't update the cards database for paypal members
                return;
            }

            $cardsForMember = $cards
                ->where('woo_customer_id', $member['id']);

            // $member['cards'] is the list of cards in WooCommerce
            $member['cards']->each(function ($memberCard) use ($issues, $member, $cardsForMember) {
                if (!$cardsForMember->contains('number', $memberCard)) {
                    $message = "{$member['first_name']} {$member['last_name']} has the card {$memberCard} but it's not listed in our database";
                    $issues->add($message);

                    return;
                }

                /** @var Card $card */
                $card = $cardsForMember->where('number', $memberCard)->first();

                if ($member['is_member'] && !$card->active) {
                    $message = "{$member['first_name']} {$member['last_name']} has the card {$memberCard} listed in " .
                        "their account but we think it's NOT active";
                    $issues->add($message);
                }

                if (!$member['is_member'] && $card->active) {
                    $message = "{$member['first_name']} {$member['last_name']} has the card {$memberCard} listed in " .
                        "their account but we think it's active";
                    $issues->add($message);
                }
            });

            $cardsForMember->each(function ($cardForMember) use ($issues, $member) {
                /** @var Card $cardForMember */
                if (!$member['cards']->contains($cardForMember->number) && $cardForMember->active) {
                    $message = "{$member['first_name']} {$member['last_name']} doesn't have {$cardForMember->number} " .
                        "listed in their profile, but we think it's active";
                    $issues->add($message);
                }

                if (!$member['cards']->contains($cardForMember->number) && $cardForMember->member_has_card) {
                    $message = "{$member['first_name']} {$member['last_name']} doesn't have {$cardForMember->number} " .
                        'listed in their profile, but we think they still have it';
                    $issues->add($message);
                }
            });
        });

        $cards
            ->filter(fn($card) => $card->member_has_card)
            ->groupBy(fn($card) => $card->number)
            ->filter(fn($value) => $value->count() > 1)
            ->each(function ($issues, $cards, $cardNum) {
                $uniqueCustomers = $cards
                    ->map(fn($card) => $card->woo_customer_id)
                    ->unique()
                    ->implode(', ');
                $numEntries = $cards->count();

                $message = "Card $cardNum has $numEntries entries in the database for customer(s): $uniqueCustomers";
                $issues->add($message);
            });

        return $issues;
    }
}
