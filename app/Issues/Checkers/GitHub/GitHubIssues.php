<?php

namespace App\Issues\Checkers\GitHub;


use App\External\GitHub\GitHubApi;
use App\Issues\Checkers\IssueCheck;
use App\Issues\Checkers\IssueCheckTrait;
use App\Issues\Data\MemberData;
use App\Issues\IssueData;
use App\Issues\Types\GitHub\InvalidUsername;
use App\Issues\Types\GitHub\NonMemberInTeam;
use App\Issues\Types\GitHub\UsernameDoesNotExist;
use App\Issues\Types\GitHub\UsernameNotListedInMembersTeam;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class GitHubIssues implements IssueCheck
{
    use IssueCheckTrait;

    private IssueData $issueData;
    private GitHubApi $gitHubApi;

    public function __construct(IssueData $issueData, GitHubApi $gitHubApi)
    {
        $this->issueData = $issueData;
        $this->gitHubApi = $gitHubApi;
    }

    protected function generateIssues(): void
    {
        $gitHubMembers = $this->issueData->gitHubTeamMembers()->map(fn($ghm) => $ghm['login']);
        /** @var Collection<MemberData> $members */
        $members = $this->issueData->members();

        foreach ($members as $member) {
            /** @var MemberData $member */
            if (is_null($member->githubUsername)) {
                continue;
            }

            $validUsername = $this->confirmValidGitHubUsername($member->githubUsername);
            if (is_null($validUsername)) {
                $this->issues->add(new UsernameDoesNotExist($member));
                continue;
            } else if (Str::lower($validUsername) != Str::lower($member->githubUsername)) {
                $this->issues->add(new InvalidUsername($member, $validUsername));
                continue;
            }

            $partOfTheTeam = $gitHubMembers
                ->filter(fn($ghm) => Str::lower($ghm) == Str::lower($member->githubUsername))
                ->isNotEmpty();

            if (!$partOfTheTeam && $member->isMember) {
                $this->issues->add(new UsernameNotListedInMembersTeam($member));
            } else if ($partOfTheTeam && !$member->isMember) {
                $this->issues->add(new NonMemberInTeam($member));
            }
        }
    }

    private function confirmValidGitHubUsername(?string $githubUsername): string|null
    {
        $matches = [];
        if(1 === preg_match(";(http(s)?)?://github.com/(?P<username>[\w-]+);", $githubUsername, $matches)) {
            $githubUsername = $matches['username'];
        }

        $data = $this->gitHubApi->userLookup($githubUsername);
        if (array_key_exists("login", $data)) {
            return $data['login'];
        }

        $data = $this->gitHubApi->emailLookup($githubUsername);  // In case they put their email instead of username
        if(!array_key_exists("total_count", $data)) {
            error_log(print_r($data, true));  // Probably rate limited
            return null;
        }
        if ($data["total_count"] == 1) {
            return $data["items"][0]["login"];
        }

        return null;
    }
}
