<?php

namespace App\DataCache;

use App\Models\Customer;
use Illuminate\Support\Collection;
use Ramsey\Uuid\Uuid;
use Spatie\LaravelData\Data;

/**
 * @property string uuid
 * @property string full_name
 * @property Customer customer
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
    ) {}

    public function __get(string $name)
    {
        return match ($name) {
            'uuid' => Uuid::uuid5(UUID::NAMESPACE_OID, $this->id),
            'full_name' => "$this->first_name $this->last_name",
            'customer' => Customer::find($this->id),
            default => null,
        };

    }
}
