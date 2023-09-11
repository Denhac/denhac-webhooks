<?php

namespace App\Issues\Checkers\InternalConsistency;

use App\Models\Customer;
use App\Issues\Checkers\IssueCheck;
use App\Issues\Checkers\IssueCheckTrait;
use App\Issues\Data\MemberData;
use App\Issues\IssueData;
use App\Issues\Types\InternalConsistency\RemoteIsMemberButLocalIsNot;
use App\Issues\Types\InternalConsistency\RemoteIsNotMemberButLocalIs;

class IsMemberIssues implements IssueCheck
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
