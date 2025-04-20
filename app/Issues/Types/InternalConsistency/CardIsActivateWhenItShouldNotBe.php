<?php

namespace App\Issues\Types\InternalConsistency;

use App\Aggregates\MembershipAggregate;
use App\DataCache\MemberData;
use App\Issues\Fixing\ICanFixThem;
use App\Issues\Types\IssueBase;
use App\StorableEvents\AccessCards\CardSentForDeactivation;

class CardIsActivateWhenItShouldNotBe extends IssueBase implements ICanFixThem
{
    private MemberData $member;

    private $memberCard;

    public function __construct(MemberData $member, $memberCard)
    {
        $this->member = $member;
        $this->memberCard = $memberCard;
    }

    public static function getIssueNumber(): int
    {
        return 208;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return 'Internal Consistency: Non member card is activate';
    }

    public function getIssueText(): string
    {
        return "{$this->member->first_name} {$this->member->last_name} has the card {$this->memberCard} and we think it's active when it should not be.";
    }

    public function fix(): bool
    {
        // Record that is fine since we just want the reactor to update
        MembershipAggregate::make($this->member->id)
            ->recordThat(new CardSentForDeactivation($this->member->id, $this->memberCard))
            ->persist();

        return true;
    }
}
