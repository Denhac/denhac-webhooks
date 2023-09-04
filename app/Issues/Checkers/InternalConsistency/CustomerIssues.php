<?php

namespace App\Issues\Checkers\InternalConsistency;

use App\Customer;
use App\Issues\Checkers\IssueCheck;
use App\Issues\Checkers\IssueCheckTrait;
use App\Issues\Data\MemberData;
use App\Issues\IssueData;
use App\Issues\Types\InternalConsistency\CannotFindCustomer;
use App\Issues\Types\InternalConsistency\CustomerDataDiffers;

class CustomerIssues implements IssueCheck
{
    use IssueCheckTrait;

    private IssueData $issueData;

    public function __construct(IssueData $issueData)
    {
        $this->issueData = $issueData;
    }

    protected function generateIssues(): void
    {
        // We grab members here because it gives easy access to meta keys and will have everything we care about.
        // Plus, most other checkers need it so there's no extra cost to use it here.
        $members = $this->issueData->members();
        $customers = Customer::all();

        // MemberData -> Customer property
        // We don't handle membership status here since that's a derived property and not directly on the customer.
        // TODO Handle primary email since that's what's in Customer object
        // TODO Handle birthday
        // TODO Handle id checked field
        $propertyMapping = [
            'id' => 'woo_id',
            'first_name' => 'first_name',
            'last_name' => 'last_name',
            'slackId' => 'slack_id',
            'githubUsername' => 'github_username',
            'stripeCardHolderId' => 'stripe_card_holder_id',
        ];

        foreach ($members as $member) {
            /** @var MemberData $member */
            if ($member->system == IssueData::SYSTEM_PAYPAL) {
                continue;
            }

            /** @var Customer $customer */
            $customer = $customers->where('woo_id', $member->id)->first();

            if (is_null($customer)) {
                $this->issues->add(new CannotFindCustomer($member));

                continue;
            }

            $differingProperties = collect();
            foreach ($propertyMapping as $memberDataKey => $customerKey) {
                if ($member->$memberDataKey != $customer->$customerKey) {
                    $differingProperties->add($memberDataKey);
                }
            }

            if ($differingProperties->isNotEmpty()) {
                $this->issues->add(new CustomerDataDiffers($member, $differingProperties));
            }
        }
    }
}
