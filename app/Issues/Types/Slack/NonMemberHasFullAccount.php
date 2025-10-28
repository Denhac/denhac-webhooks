<?php

namespace App\Issues\Types\Slack;

use App\Actions\SetUltraRestrictedUser;
use App\DataCache\MemberData;
use App\Issues\Types\ICanFixThem;
use App\Issues\Types\IssueBase;

class NonMemberHasFullAccount extends IssueBase
{
    use ICanFixThem;

    private MemberData $member;

    private $slackUser;

    public function __construct(MemberData $member, $slackUser)
    {
        $this->member = $member;
        $this->slackUser = $slackUser;
    }

    public static function getIssueNumber(): int
    {
        return 303;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return 'Slack: Non member has full account';
    }

    public function getIssueText(): string
    {
        return "{$this->member->first_name} {$this->member->last_name} with slack id ({$this->slackUser['id']}) is not an active member but they have a full slack account.";
    }

    public function fix(): bool
    {
        return $this->issueFixChoice()
            ->option('Deactivate Slack account', function () {
                app(SetUltraRestrictedUser::class)->execute($this->member->customer);

                return true;
            })
            ->run();
    }
}
