<?php

namespace App\Issues\Checkers;


use App\ActiveCardHolderUpdate;
use App\Issues\IssueData;
use Illuminate\Support\Collection;

class ActiveCardIssues implements IssueCheck
{
    private IssueData $issueData;

    public function __construct(IssueData $issueData)
    {
        $this->issueData = $issueData;
    }

    public function issueTitle(): string
    {
        return "Issue with a card";
    }

    public function getIssues(): Collection
    {
        $issues = collect();
        $members = $this->issueData->members();

        /** @var ActiveCardHolderUpdate $activeCardHolderUpdate */
        $activeCardHolderUpdate = ActiveCardHolderUpdate::latest()->first();
        if (is_null($activeCardHolderUpdate)) {
            return $issues;
        }

        $card_holders = collect($activeCardHolderUpdate->card_holders);
        $card_holders
            ->each(function ($card_holder) use ($issues, $members) {
                $membersWithCard = $members
                    ->filter(function ($member) use ($card_holder) {
                        return $member['cards']->contains(ltrim($card_holder['card_num'], '0'));
                    });

                if ($membersWithCard->count() == 0) {
                    $message = "{$card_holder['first_name']} {$card_holder['last_name']} has the active card ({$card_holder['card_num']}) but I have no membership record of them with that card.";
                    $issues->add($message);

                    return;
                }

                if ($membersWithCard->count() > 1) {
                    $message = "{$card_holder['first_name']} {$card_holder['last_name']} has the active card ({$card_holder['card_num']}) but is connected to multiple accounts.";
                    $issues->add($message);

                    return;
                }

                $member = $membersWithCard->first();

                if ($card_holder['first_name'] != $member['first_name'] ||
                    $card_holder['last_name'] != $member['last_name']) {
                    $message = "{$card_holder['first_name']} {$card_holder['last_name']} has the active card ({$card_holder['card_num']}) but is listed as {$member['first_name']} {$member['last_name']} in our records.";
                    $issues->add($message);
                }

                if (! $member['is_member']) {
                    $message = "{$card_holder['first_name']} {$card_holder['last_name']} has the active card ({$card_holder['card_num']}) but is not currently a member.";
                    $issues->add($message);
                }
            });

        $members
            ->filter(function ($member) {
                return ! is_null($member['first_name']) &&
                    ! is_null($member['last_name']) &&
                    $member['is_member'] &&
                    $member['has_signed_waiver'];
            })
            ->each(function ($member) use ($card_holders, $issues) {
                $member['cards']->each(function ($card) use ($member, $card_holders, $issues) {
                    $cardActive = $card_holders->contains('card_num', $card);
                    if (! $cardActive) {
                        $message = "{$member['first_name']} {$member['last_name']} has the card $card but it doesn't appear to be active";
                        $issues->add($message);
                    }
                });
            });

        return $issues;
    }
}
