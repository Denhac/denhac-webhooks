<?php

namespace App\Issues\Types\InternalConsistency;

use App\Aggregates\MembershipAggregate;
use App\Issues\Types\ICanFixThem;
use App\Issues\Types\IssueBase;
use App\UserMembership;

class UserMembershipNotFoundOnRemote extends IssueBase
{
    use ICanFixThem;

    private int $userMembershipId;

    public function __construct($userMembershipId)
    {
        $this->userMembershipId = $userMembershipId;
    }

    public static function getIssueNumber(): int
    {
        return 214;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return 'Internal Consistency: User membership not found on remote';
    }

    public function getIssueText(): string
    {
        return "User Membership $this->userMembershipId exists in our local database but not on the website. Deleted?";
    }

    public function fix(): bool
    {
        return $this->issueFixChoice()
            ->option('Delete local user membership', function () {
                /** @var UserMembership $user_membership */
                $userMembership = UserMembership::find($this->userMembershipId);

                MembershipAggregate::make($userMembership->customer_id)
                    ->deleteUserMembership(['id' => $userMembership->id])
                    ->persist();

                return true;
            })
            ->run();
    }
}
