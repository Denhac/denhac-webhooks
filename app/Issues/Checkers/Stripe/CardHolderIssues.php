<?php

namespace App\Issues\Checkers\Stripe;

use App\DataCache\AggregateCustomerData;
use App\DataCache\MemberData;
use App\DataCache\StripeCardHolders;
use App\Issues\Checkers\IssueCheck;
use App\Issues\Checkers\IssueCheckTrait;
use App\Issues\Types\Stripe\MultipleMembersForCardHolder;
use App\Issues\Types\Stripe\NoCardHolderFoundForId;
use App\Issues\Types\Stripe\NoMemberForCardHolder;
use Stripe\Issuing\Cardholder;

class CardHolderIssues /* implements IssueCheck */
{
    use IssueCheckTrait;

    public function __construct(
        private readonly AggregateCustomerData $aggregateCustomerData,
        private readonly StripeCardHolders $stripeCardHolders
    ) {}

    protected function generateIssues(): void
    {
        $members = $this->aggregateCustomerData->get();
        $cardHolders = $this->stripeCardHolders->get();

        foreach ($cardHolders as $cardHolder) {
            /** @var Cardholder $cardHolder */
            $membersWithThatStripeId = $members->filter(fn ($m) => $m->stripeCardHolderId == $cardHolder['id']);

            if ($membersWithThatStripeId->isEmpty()) {
                if ($cardHolder->status == 'active') {
                    $this->issues->add(new NoMemberForCardHolder($cardHolder));
                }
            } elseif ($membersWithThatStripeId->count() > 1) {
                // More than one member has that id
                $this->issues->add(new MultipleMembersForCardHolder($membersWithThatStripeId, $cardHolder));
            }
        }

        foreach ($members as $member) {
            /** @var MemberData $member */
            if (is_null($member->stripeCardHolderId)) {
                continue;
            }
            $cardHolder = $cardHolders->where('id', $member->stripeCardHolderId)->first();

            if (is_null($cardHolder)) {
                $this->issues->add(new NoCardHolderFoundForId($member));
            }
        }
    }
}
