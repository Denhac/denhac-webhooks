<?php

namespace App\Issues\Types\GoogleGroups;

use App\External\Google\GoogleApi;
use App\Issues\Data\MemberData;
use App\Issues\Types\ICanFixThem;
use App\Issues\Types\IssueBase;

class NotActiveMemberButInGroups extends IssueBase
{
    use ICanFixThem;

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
        return 'Google Groups: Not active member found in groups';
    }

    public function getIssueText(): string
    {
        return "{$this->member->first_name} {$this->member->last_name} with email ($this->email) is not an active member but is in groups: {$this->groupsForEmail->implode(', ')}";
    }

    public function fix(): bool
    {
        return $this->issueFixChoice()
            ->option('Remove member from groups', function () {
                /** @var GoogleApi $googleApi */
                $googleApi = app(GoogleApi::class);
                foreach ($this->groupsForEmail as $group) {
                    $googleApi->group($group)->remove($this->email);
                }

                return true;
            })
            ->run();
    }
}
