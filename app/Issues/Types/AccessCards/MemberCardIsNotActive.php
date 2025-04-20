<?php

namespace App\Issues\Types\AccessCards;

use App\Aggregates\MembershipAggregate;
use App\DataCache\MemberData;
use App\Issues\Fixing\Fixable;
use App\Issues\Fixing\ICanFixThem;
use App\Issues\Types\IssueBase;
use App\StorableEvents\AccessCards\CardSentForActivation;

class MemberCardIsNotActive extends IssueBase implements ICanFixThem
{
    public MemberData $member;

    public $cardNumber;

    public function __construct(MemberData $member, $cardNumber)
    {
        $this->member = $member;
        $this->cardNumber = $cardNumber;
    }

    public static function getIssueNumber(): int
    {
        return 4;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return 'Access Cards: Member card is not active';
    }

    public function getIssueText(): string
    {
        return "{$this->member->first_name} {$this->member->last_name} has the access card $this->cardNumber but it doesn't appear to be active";
    }

    function fix(): bool
    {
        MembershipAggregate::make($this->member->id)
            ->recordThat(new CardSentForActivation($this->member->id, $this->cardNumber))
            ->persist();

        return true;
    }
}
