<?php

namespace App\Issues\Checkers;


use App\Customer;
use App\Issues\IssueData;
use Illuminate\Support\Collection;

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

        foreach($members as $member) {
            /** @var Customer $customer */
            $customer = $customers->where('woo_id', $member['id'])->first();
            if ($member['is_member'] && ! $customer->member) {  // Remote says member, we don't.
//                $this->issues->add(new )
            }
        }
    }
}
