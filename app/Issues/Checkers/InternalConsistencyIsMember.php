<?php

namespace App\Issues\Checkers;


use App\Customer;
use App\Issues\IssueData;
use App\Issues\Types\InternalConsistency\CannotFindCustomer;
use App\Issues\Types\InternalConsistency\RemoteIsMemberButLocalIsNot;
use App\Issues\Types\InternalConsistency\RemoteIsNotMemberButLocalIs;

class InternalConsistencyIsMember implements IssueCheck
{
    use IssueCheckTrait;

    private IssueData $issueData;

    public function __construct(IssueData $issueData)
    {
        $this->issueData = $issueData;
    }

    public function issueTitle(): string
    {
        return "Descriptive title of this category of issues";
    }

    protected function generateIssues(): void
    {
        $members = $this->issueData->members();
        $customers = Customer::all();

        foreach ($members as $member) {
            if($member['system'] == IssueData::SYSTEM_PAYPAL) {
                continue;
            }

            /** @var Customer $customer */
            $customer = $customers->where('woo_id', $member['id'])->first();

            if(is_null($customer)) {
                $this->issues->add(new CannotFindCustomer($member));
            } else if ($member['is_member'] && !$customer->member) {  // Remote says member, we don't.
                $this->issues->add(new RemoteIsMemberButLocalIsNot($member));
            } else if (!$member['is_member'] && $customer->member) {  // Remote says not a member, we do.
                $this->issues->add(new RemoteIsNotMemberButLocalIs($member));
            }
        }
    }
}
