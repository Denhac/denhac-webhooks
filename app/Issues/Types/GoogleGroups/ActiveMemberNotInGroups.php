<?php

namespace App\Issues\Types\GoogleGroups;

use App\DataCache\MemberData;
use App\External\Google\GoogleApi;
use App\Issues\Types\ICanFixThem;
use App\Issues\Types\IssueBase;
use Illuminate\Support\Str;

class ActiveMemberNotInGroups extends IssueBase
{
    use ICanFixThem;

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
        return 'Google Groups: Active member not found in groups';
    }

    public function getIssueText(): string
    {
        $first_name = $this->member->first_name;
        $last_name = $this->member->last_name;
        $memberEmails = $this->member->emails;
        $membersGroupsMissing = $this->memberGroupsMissing->implode(', ');
        $groupString = Str::plural('group', $this->memberGroupsMissing->count());
        $membersEmailsString = $memberEmails->implode(', ');
        $emailString = Str::plural('email', $memberEmails->count());

        return "$first_name $last_name with $emailString ({$membersEmailsString}) is an active member but is not in $groupString $membersGroupsMissing";
    }

    public function fix(): bool
    {
        return $this->issueFixChoice()
            ->option('Add member to groups', function () {
                /** @var GoogleApi $googleApi */
                $googleApi = app(GoogleApi::class);
                foreach ($this->memberGroupsMissing as $group) {
                    $googleApi->group($group)->add($this->member->primaryEmail);
                }

                return true;
            })
            ->run();
    }
}
