<?php

namespace App\Issues\Types\Stripe;

use App\Issues\Types\IssueBase;
use Illuminate\Support\Collection;
use Stripe\Issuing\Cardholder;

class MultipleMembersForCardHolder extends IssueBase
{
    private Collection $membersWithThatStripeId;

    private Cardholder $cardHolder;

    public function __construct(Collection $membersWithThatStripeId, CardHolder $cardHolder)
    {
        $this->membersWithThatStripeId = $membersWithThatStripeId;
        $this->cardHolder = $cardHolder;
    }

    public static function getIssueNumber(): int
    {
        return 501;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return 'Stripe: Multiple members for card holder';
    }

    public function getIssueText(): string
    {
        $id = $this->cardHolder->id;
        $name = $this->cardHolder->name;
        $members = $this->membersWithThatStripeId
            ->map(fn ($m) => "{$m->first_name} {$m->last_name}")
            ->implode(', ');

        return "CardHolder for \"$name\" ($id) is assigned to multiple members: $members";
    }
}
