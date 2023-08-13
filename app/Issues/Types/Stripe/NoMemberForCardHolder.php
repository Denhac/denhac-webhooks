<?php

namespace App\Issues\Types\Stripe;

use App\Issues\Types\IssueBase;
use Stripe\Issuing\Cardholder;

class NoMemberForCardHolder extends IssueBase
{
    private Cardholder $cardHolder;

    public function __construct(Cardholder $cardHolder)
    {
        $this->cardHolder = $cardHolder;
    }

    public static function getIssueNumber(): int
    {
        return 500;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return "Stripe: No member for card holder";
    }

    public function getIssueText(): string
    {
        $id = $this->cardHolder->id;
        $name = $this->cardHolder->name;
        return "CardHolder for \"$name\" ($id) is not assigned to any member";
    }
}
