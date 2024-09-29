<?php

namespace App\Issues\Checkers\AccessCards;


use App\DataCache\AggregateCustomerData;
use App\DataCache\MemberData;
use App\Issues\Checkers\IssueCheck;
use App\Issues\Checkers\IssueCheckTrait;
use App\Issues\Types\AccessCards\CustomerHasBothCardAndTemporaryCode;
use App\Issues\Types\AccessCards\NoTemporaryCodeOnCustomerWithoutIdCheck;
use App\Models\UserMembership;
use Illuminate\Support\Collection;

class TemporaryCodeIssues implements IssueCheck
{
    use IssueCheckTrait;

    public function __construct(
        private readonly AggregateCustomerData $aggregateCustomerData,
    )
    {
    }

    protected function generateIssues(): void
    {
        /** @var Collection<MemberData> $members */
        $members = $this->aggregateCustomerData->get();

        foreach ($members as $member) {
            /** @var MemberData $member */
            if ($member->cards->count() > 0 && ! is_null($member->accessCardTemporaryCode)) {
                $this->issues->add(new CustomerHasBothCardAndTemporaryCode($member));
            }

            $fullMemberUserMembership = $member->userMemberships
                ->first(fn($um) => $um['plan_id'] == UserMembership::MEMBERSHIP_FULL_MEMBER);

            // Some users may have a cancelled user membership before they ever got to the ID check stage.
            $hasPausedUserMembership = ! is_null($fullMemberUserMembership) &&
                $fullMemberUserMembership['status'] == 'paused';

            if (! $member->idChecked &&
                is_null($member->accessCardTemporaryCode) &&
                $hasPausedUserMembership) {
                // This issue is important because they won't be able to get their card once their id is checked
                $this->issues->add(new NoTemporaryCodeOnCustomerWithoutIdCheck($member));
            }
        }
    }
}
