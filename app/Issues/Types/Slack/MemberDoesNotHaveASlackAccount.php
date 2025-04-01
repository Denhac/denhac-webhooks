<?php

namespace App\Issues\Types\Slack;

use App\DataCache\MemberData;
use App\Issues\Types\ICanFixThem;
use App\Issues\Types\IssueBase;
use App\Jobs\MakeCustomerRegularMemberInSlack;

class MemberDoesNotHaveASlackAccount extends IssueBase
{
    use ICanFixThem;

    private MemberData $member;

    public function __construct(MemberData $member)
    {
        $this->member = $member;
    }

    public static function getIssueNumber(): int
    {
        return 300;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return 'Slack: Member does not have a slack account';
    }

    public function getIssueText(): string
    {
        return "{$this->member->first_name} {$this->member->last_name} ({$this->member->id}) doesn't appear to have a slack account";
    }

    public function fix(): bool
    {
        return $this->issueFixChoice()
            ->defaultOption('Activate Slack account', function () {
                dispatch(new MakeCustomerRegularMemberInSlack($this->member->id));

                return true;
            })
            ->run();
    }
}
