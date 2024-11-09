<?php

namespace App\Issues\Checkers\InternalConsistency;

use App\DataCache\AggregateCustomerData;
use App\DataCache\MemberData;
use App\Issues\Checkers\IssueCheck;
use App\Issues\Checkers\IssueCheckTrait;
use App\Issues\Types\InternalConsistency\RemoteIsMemberButLocalIsNot;
use App\Issues\Types\InternalConsistency\RemoteIsNotMemberButLocalIs;
use App\Models\Customer;

class IsMemberIssues implements IssueCheck
{
    use IssueCheckTrait;

    public function __construct(
        private readonly AggregateCustomerData $aggregateCustomerData
    ) {}

    protected function generateIssues(): void
    {
        $members = $this->aggregateCustomerData->get();
        $customers = Customer::all();

        foreach ($members as $member) {
            /** @var MemberData $member */

            /** @var Customer $customer */
            $customer = $customers->find($member->id);

            if (is_null($customer)) {
                continue;  // CustomerIssues handles reporting this error
            } elseif ($member->isMember && ! $customer->member) {  // Remote says member, we don't.
                $this->issues->add(new RemoteIsMemberButLocalIsNot($member));
            } elseif (! $member->isMember && $customer->member) {  // Remote says not a member, we do.
                $this->issues->add(new RemoteIsNotMemberButLocalIs($member));
            }
        }
    }
}
