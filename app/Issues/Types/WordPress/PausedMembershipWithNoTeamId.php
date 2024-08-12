<?php

namespace App\Issues\Types\WordPress;

use App\DataCache\MemberData;
use App\Issues\Types\IssueBase;

class PausedMembershipWithNoTeamId extends IssueBase
{
    public function __construct(
        private readonly MemberData $memberData,
        private readonly array      $userMembership
    )
    {
    }

    public static function getIssueNumber(): int
    {
        return 601;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return "Word Press: Paused membership with no team id";
    }

    public function getIssueText(): string
    {
        return "{$this->memberData->full_name} ({$this->memberData->id}) has user membership ({$this->userMembership['id']}) with status "
            . "\"{$this->userMembership['status']}\" with no team id";
    }
}
