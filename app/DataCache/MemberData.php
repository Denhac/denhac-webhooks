<?php

namespace App\DataCache;

use Illuminate\Support\Collection;
use Ramsey\Uuid\Uuid;
use Spatie\LaravelData\Data;

/**
 * @property string uuid
 * @property string full_name
 */
class MemberData extends Data
{
    public function __construct(
        public int|string $id,  // TODO string only because PayPal
        public string $first_name,
        public string $last_name,
        public string $primaryEmail,
        public Collection $emails,
        public bool $idChecked,
        public bool $isMember,
        public bool $hasSignedWaiver,
        public Collection $subscriptions,
        public Collection $userMemberships,
        public Collection $cards,
        public ?string $slackId,
        public ?string $githubUsername,
        public ?string $stripeCardHolderId,
        public ?string $accessCardTemporaryCode,
    ) {
    }

    public function __get(string $name)
    {
        if ($name == 'uuid') {
            return Uuid::uuid5(UUID::NAMESPACE_OID, $this->id);
        } elseif ($name == 'full_name') {
            return "$this->first_name $this->last_name";
        }

        return null;
    }
}
