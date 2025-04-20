<?php

namespace App\Issues\Types\GitHub;

use App\DataCache\MemberData;
use App\External\GitHub\GitHubApi;
use App\Issues\FixChooser;
use App\Issues\Fixing\Fixable;
use App\Issues\Types\IssueBase;

class NonMemberInOrganization extends IssueBase implements Fixable
{
    private MemberData $member;

    public function __construct(MemberData $member)
    {
        $this->member = $member;
    }

    public static function getIssueNumber(): int
    {
        return 401;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return 'GitHub: Non member in denhac organization';
    }

    public function getIssueText(): string
    {
        return "{$this->member->first_name} {$this->member->last_name} is not an active member but their GitHub username ({$this->member->githubUsername}) is in the denhac organization";
    }

    public function fix(): bool
    {
        return FixChooser::new()
            ->defaultOption('Remove from GitHub team', function () {
                /** @var GitHubApi $gitHubApi */
                $gitHubApi = app(GitHubApi::class);

                $gitHubApi->denhac()->remove($this->member->githubUsername);

                return true;
            })
            ->fix();
    }
}
