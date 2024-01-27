<?php

namespace App\Issues\Types\GitHub;

use App\External\GitHub\GitHubApi;
use App\Issues\Data\MemberData;
use App\Issues\Types\ICanFixThem;
use App\Issues\Types\IssueBase;

class UsernameNotListedInOrganization extends IssueBase
{
    use ICanFixThem;

    private MemberData $member;

    public function __construct(MemberData $member)
    {
        $this->member = $member;
    }

    public static function getIssueNumber(): int
    {
        return 400;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return 'GitHub: Username not listed in denhac organization';
    }

    public function getIssueText(): string
    {
        return "{$this->member->first_name} {$this->member->last_name} is an active member but their GitHub username ({$this->member->githubUsername}) is not in the denhac organization";
    }

    public function fix(): bool
    {
        return $this->issueFixChoice()
            ->option('Add to GitHub team', function () {
                /** @var GitHubApi $gitHubApi */
                $gitHubApi = app(GitHubApi::class);
                $gitHubApi->denhac()->team('members')->add($this->member->githubUsername);

                return true;
            })
            ->run();
    }
}
