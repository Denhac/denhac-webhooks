<?php

namespace App\DataCache;

use Illuminate\Support\Collection;
use Ramsey\Uuid\Uuid;
use Spatie\LaravelData\Data;
use function Laravel\Prompts\info;
use function Laravel\Prompts\search;

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
    ) {}

    public function __get(string $name)
    {
        if ($name == 'uuid') {
            return Uuid::uuid5(UUID::NAMESPACE_OID, $this->id);
        } elseif ($name == 'full_name') {
            return "$this->first_name $this->last_name";
        }

        return null;
    }

    public static function byID($id): ?MemberData
    {
        /** @var AggregateCustomerData $aggregateCustomerData */
        $aggregateCustomerData = app(AggregateCustomerData::class);

        return $aggregateCustomerData->get()->filter(fn ($m) => $m->id == $id)->first();
    }

    public static function selectMember(): ?MemberData
    {
        /** @var AggregateCustomerData $aggregateCustomerData */
        $aggregateCustomerData = app(AggregateCustomerData::class);
        $memberNames = $aggregateCustomerData->get()
            ->mapWithKeys(fn ($m) => [$m => "$m->first_name $m->last_name"]);

        do {
            $choice = search(
                label: 'Select customer/member',
                options: $memberNames,
                required: false,
            );

            if (empty($choice)) {
                break;  // They decided to exit without entering anything
            }

            if ($memberNames->has($choice)) {
                break;  // They selected a valid member name
            }

            info("$choice is not a valid option. Please select a member or enter no text to cancel.");
        } while (true);

        if (empty($choice)) {
            return null;
        }

        return $memberNames->get($choice);
    }
}
