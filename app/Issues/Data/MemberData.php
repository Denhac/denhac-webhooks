<?php

namespace App\Issues\Data;


use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class MemberData extends Data
{
    public function __construct(
        public int|string $id,  // TODO string only because PayPal
        public string $first_name,
        public string $last_name,
        public Collection $emails,
        public bool $isMember,
        public bool $hasSignedWaiver,
        public Collection $subscriptions,
        public Collection $userMemberships,
        public Collection $cards,
        public string|null $slackId,
        public string|null $githubUsername,
        public string|null $stripeCardHolderId,
        public string $system,
    )
    {
    }
}
