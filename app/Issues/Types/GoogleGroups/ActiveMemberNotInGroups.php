<?php

namespace App\Issues\Types\GoogleGroups;


use App\Issues\Data\MemberData;
use App\Issues\Types\IssueBase;
use Illuminate\Support\Str;

class ActiveMemberNotInGroups extends IssueBase
{
    private MemberData $member;
    private $memberGroupsMissing;

    public function __construct(MemberData $member, $memberGroupsMissing)
    {
        $this->member = $member;
        $this->memberGroupsMissing = $memberGroupsMissing;
    }

    public static function getIssueNumber(): int
    {
        return 104;
    }

    public static function getIssueTitle(): string
    {
        return "Google Groups: Active member not found in groups";
    }

    public function getIssueText(): string
    {
        $first_name = $this->member->first_name;
        $last_name = $this->member->last_name;
        $memberEmails = $this->member->emails;
        $membersGroupsMissing = $this->memberGroupsMissing->implode(', ');
        $groupString = Str::plural("group", $this->memberGroupsMissing->count());
        $membersEmailsString = $memberEmails->implode(', ');
        $emailString = Str::plural("email", $memberEmails->count());
        return "$first_name $last_name with $emailString ({$membersEmailsString}) is an active member but is not in $groupString $membersGroupsMissing";
    }
}
