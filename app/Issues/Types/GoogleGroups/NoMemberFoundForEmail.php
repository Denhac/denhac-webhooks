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
        $this->email = $email;
        $this->groupsForEmail = $groupsForEmail;
    }

    public static function getIssueNumber(): int
    {
        return 105;
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
