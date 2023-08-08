<?php

namespace App\Issues\Types\GoogleGroups;


use App\Issues\Types\IssueBase;
use Illuminate\Support\Collection;

class TwoMembersSameEmail extends IssueBase
{
    private string $email;
    private Collection $membersForEmail;

    public function __construct(string $email, Collection $membersForEmail)
    {
        parent::__construct(IssueBase::ISSUE_GOOGLE_GROUP_TWO_MEMBERS_HAVE_SAME_EMAIL);

        $this->email = $email;
        $this->membersForEmail = $membersForEmail;
    }

    public static function getIssueTitle(): string
    {
        return "Google Groups: Two members have the same email";
    }

    public function getIssueText(): string
    {
        return "More than 2 members exist for email address $this->email: {$this->membersForEmail->implode(', ')}";
    }
}
