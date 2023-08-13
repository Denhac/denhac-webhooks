<?php

namespace App\Issues\Checkers\Stripe;


use App\Issues\Checkers\IssueCheck;
use App\Issues\Checkers\IssueCheckTrait;
use App\Issues\Data\MemberData;
use App\Issues\IssueData;
use App\Issues\Types\Stripe\MultipleMembersForCardHolder;
use App\Issues\Types\Stripe\NoCardHolderFoundForId;
use App\Issues\Types\Stripe\NoMemberForCardHolder;

class CardHolderIssues implements IssueCheck
{
    use IssueCheckTrait;

    private IssueData $issueData;

    public function __construct(IssueData $issueData)
    {
        $this->issueData = $issueData;
    }

    protected function generateIssues(): void
    {
        $members = $this->issueData->members();
        $cardHolders = $this->issueData->stripeCardHolders();

        foreach($cardHolders as $cardHolder) {
            $membersWithThatStripeId = $members->filter(fn($m) => $m->stripeCardHolderId == $cardHolder['id']);

            if($membersWithThatStripeId->isEmpty()) {
                $this->issues->add(new NoMemberForCardHolder($cardHolder));
            } else if($membersWithThatStripeId->count() > 1) {
                // More than one member has that id
                $this->issues->add(new MultipleMembersForCardHolder($membersWithThatStripeId, $cardHolder));
            }
        }

        foreach($members as $member) {
            /** @var MemberData $member */
            if(is_null($member->stripeCardHolderId)) {
                continue;
            }
            $cardHolder = $cardHolders->where('id', $member->stripeCardHolderId)->first();

            if(is_null($cardHolder)) {
                $this->issues->add(new NoCardHolderFoundForId($member));
            }
        }
    }
}
