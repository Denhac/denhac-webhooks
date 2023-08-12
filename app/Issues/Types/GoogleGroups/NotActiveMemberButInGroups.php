<?php

namespace App\Issues\Types\GoogleGroups;


use App\Issues\Data\MemberData;
use App\Issues\Types\IssueBase;

class NotActiveMemberButInGroups extends IssueBase
{
    private MemberData $member;
    private $email;
    private $groupsForEmail;

    public function __construct(MemberData $member, $email, $groupsForEmail)
    {
        $this->member = $member;
        $this->email = $email;
        $this->groupsForEmail = $groupsForEmail;
    }

    public static function getIssueNumber(): int
    {
        return 103;
    }

    public static function getIssueTitle(): string
    {
        return "Google Groups: Not active member found in groups";
    }

    public function getIssueText(): string
    {
        return "{$this->member->first_name} {$this->member->last_name} with email ($this->email) is not an active member but is in groups: {$this->groupsForEmail->implode(', ')}";
    }
}
