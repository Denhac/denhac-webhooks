<?php

namespace App\Issues\Types\AccessCards;

use App\Aggregates\MembershipAggregate;
use App\Issues\Data\MemberData;
use App\Issues\Types\ICanFixThem;
use App\Issues\Types\IssueBase;
use App\StorableEvents\CardSentForActivation;

class MemberCardIsNotActive extends IssueBase
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

    public function fix(): bool
    {
        return $this->issueFixChoice()
            ->option('Activate Card', function () {
                MembershipAggregate::make($this->member->id)
                    ->recordThat(new CardSentForActivation($this->member->id, $this->cardNumber))
                    ->persist();

                return true;
            })
            ->run();
    }
}
