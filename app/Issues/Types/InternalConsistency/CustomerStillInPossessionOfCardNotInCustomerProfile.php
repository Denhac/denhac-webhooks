<?php

namespace App\Issues\Types\InternalConsistency;

use App\Aggregates\MembershipAggregate;
use App\Issues\Data\MemberData;
use App\Issues\Types\ICanFixThem;
use App\Issues\Types\IssueBase;
use App\StorableEvents\AccessCards\CardRemoved;

class CustomerStillInPossessionOfCardNotInCustomerProfile extends IssueBase
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
        return 210;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return 'Internal Consistency: Customer still in possession of card not in customer profile';
    }

    public function getIssueText(): string
    {
        return "{$this->member->first_name} {$this->member->last_name} doesn't have {$this->cardNumber} ".
            'listed in their profile, but local database thinks they still have it';
    }

    public function fix(): bool
    {
        return $this->issueFixChoice()
            ->option('Send CardRemoved event', function () {
                // Record that is fine since we just want the projector to update
                MembershipAggregate::make($this->member->id)
                    ->recordThat(new CardRemoved($this->member->id, $this->cardNumber))
                    ->persist();

                return true;
            })
            ->run();
    }
}
