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
        $this->email = $email;
        $this->membersForEmail = $membersForEmail;
    }

    public static function getIssueNumber(): int
    {
        return 104;
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
