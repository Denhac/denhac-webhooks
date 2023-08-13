<?php

namespace App\Issues\Types\AccessCards;

use App\Aggregates\MembershipAggregate;
use App\Issues\Data\MemberData;
use App\Issues\Types\ICanFixThem;
use App\Issues\Types\IssueBase;
use App\StorableEvents\CardSentForDeactivation;

class NonMemberHasActiveCard extends IssueBase
{
    use ICanFixThem;

    private MemberData $member;
    private $cardNumber;

    public function __construct(MemberData $member, $cardNumber)
    {
        $this->member = $member;
        $this->cardNumber = $cardNumber;
    }

    public static function getIssueNumber(): int
    {
        return 3;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return "Access Cards: Non member has active card";
    }

    public function getIssueText(): string
    {
        return "{$this->member->first_name} {$this->member->last_name} has the active access card ({$this->cardNumber}) but is not currently a member.";
    }

    public function fix(): bool
    {
        $DEACTIVATE_CARD = "Deactivate Card";
        $CANCEL = "Cancel";

        $choice = $this->choice("How do you want to fix this issue?", [$DEACTIVATE_CARD, $CANCEL]);

        if ($choice == $DEACTIVATE_CARD) {
            MembershipAggregate::make($this->member->id)
                ->recordThat(new CardSentForDeactivation($this->member->id, $this->cardNumber))
                ->persist();
            return true;
        }

        return false;
    }
}
