<?php

namespace App\Issues\Types\GoogleGroups;


use App\Issues\Types\IssueBase;
use Illuminate\Support\Collection;

class NoMemberFoundForEmail extends IssueBase
{
    private string $email;
    private Collection $groupsForEmail;

    public function __construct(string $email, Collection $groupsForEmail)
    {
        parent::__construct(IssueBase::ISSUE_GOOGLE_GROUP_NO_MEMBER_FOUND_FOR_EMAIL);
        $this->email = $email;
        $this->groupsForEmail = $groupsForEmail;
    }

    public static function getIssueTitle(): string
    {
        return "Google Groups: No member found for email";
    }

    public function getIssueText(): string
    {
        return "No member found for email address $this->email in groups: {$this->groupsForEmail->implode(', ')}";
    }
}
