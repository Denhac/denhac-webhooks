<?php

namespace App\Issues\Checkers\InternalConsistency;


use App\Customer;
use App\Issues\Checkers\IssueCheck;
use App\Issues\Checkers\IssueCheckTrait;
use App\Issues\Data\MemberData;
use App\Issues\IssueData;
use App\Issues\Types\InternalConsistency\CannotFindCustomer;
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
            if ($member->system == IssueData::SYSTEM_PAYPAL) {
                continue;
            }

            /** @var Customer $customer */
            $customer = $customers->where('woo_id', $member->id)->first();

            if (is_null($customer)) {
                continue;  // CustomerIssues handles reporting this error
            } else if ($member->isMember && !$customer->member) {  // Remote says member, we don't.
                $this->issues->add(new RemoteIsMemberButLocalIsNot($member));
            } else if (!$member->isMember && $customer->member) {  // Remote says not a member, we do.
                $this->issues->add(new RemoteIsNotMemberButLocalIs($member));
            }
        }
    }
}
