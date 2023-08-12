<?php

namespace App\Issues\Checkers;


use App\ActiveCardHolderUpdate;
use App\Issues\IssueData;
use App\Issues\Types\AccessCards\ActiveCardMultipleAccounts;
use App\Issues\Types\AccessCards\ActiveCardNoRecord;
use App\Issues\Types\AccessCards\CardHolderIncorrectName;
use App\Issues\Types\AccessCards\MemberCardIsNotActive;
use App\Issues\Types\AccessCards\NonMemberHasActiveCard;

class ActiveCardIssues implements IssueCheck
{
    use IssueCheckTrait;

    private IssueData $issueData;

    public function __construct(IssueData $issueData)
    {
        $this->issueData = $issueData;
    }

    public function generateIssues(): void
    {
        $members = $this->issueData->members();

        /** @var ActiveCardHolderUpdate $activeCardHolderUpdate */
        $activeCardHolderUpdate = ActiveCardHolderUpdate::latest()->first();
        if (is_null($activeCardHolderUpdate)) {
            return;  // TODO Issue for no active card holder update
        }

        $card_holders = collect($activeCardHolderUpdate->card_holders);
        $card_holders
            ->each(function ($card_holder) use ($members) {
                $membersWithCard = $members
                    ->filter(function ($member) use ($card_holder) {
                        return $member['cards']->contains(ltrim($card_holder['card_num'], '0'));
                    });

                if ($membersWithCard->count() == 0) {
                    $this->issues->add(new ActiveCardNoRecord($card_holder));

                    return;
                }

                if ($membersWithCard->count() > 1) {
                    $this->issues->add(new ActiveCardMultipleAccounts($card_holder));

                    return;
                }

                $member = $membersWithCard->first();

                if ($card_holder['first_name'] != $member['first_name'] ||
                    $card_holder['last_name'] != $member['last_name']) {
                    $this->issues->add(new CardHolderIncorrectName($card_holder, $member));
                }

                if (!$member['is_member']) {
                    $this->issues->add(new NonMemberHasActiveCard($card_holder));
                }
            });

        $members
            ->filter(function ($member) {
                return !is_null($member['first_name']) &&
                    !is_null($member['last_name']) &&
                    $member['is_member'] &&
                    $member['has_signed_waiver'];
            })
            ->each(function ($member) use ($card_holders) {
                $member['cards']->each(function ($card) use ($member, $card_holders) {
                    $cardActive = $card_holders->contains('card_num', $card);
                    if (!$cardActive) {
                        $this->issues->add(new MemberCardIsNotActive($member, $card));
                    }
                });
            });
    }
}
