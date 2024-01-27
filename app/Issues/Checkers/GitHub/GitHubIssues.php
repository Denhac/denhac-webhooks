<?php

namespace App\Issues\Checkers\GitHub;

use App\External\GitHub\GitHubApi;
use App\Issues\Checkers\IssueCheck;
use App\Issues\Checkers\IssueCheckTrait;
use App\Issues\Data\MemberData;
use App\Issues\IssueData;
use App\Issues\Types\GitHub\InvalidUsername;
use App\Issues\Types\GitHub\NonMemberInOrganization;
use App\Issues\Types\GitHub\UnknownGitHubUsernameOrganization;
use App\Issues\Types\GitHub\UsernameDoesNotExist;
use App\Issues\Types\GitHub\UsernameNotListedInOrganization;
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
        $gitHubMembers = $this->issueData->gitHubMembers()->map(fn($ghm) => $ghm['login']);
        $gitHubPendingMembers = $this->issueData->gitHubPendingMembers()->map(fn($ghm) => $ghm['login']);
        $gitHubFailedInvites = $this->issueData->gitHubFailedInvites()->map(fn($ghm) => $ghm['login']);
        /** @var Collection<MemberData> $members */
        $members = $this->issueData->members()->filter(fn($member) => ! is_null($member->githubUsername));

        $progress = $this->issueData->apiProgress('Checking GitHub users');
        $progress->setProgress(0, $members->count());
        foreach ($members as $member) {
            $progress->step();

            /** @var MemberData $member */

            $validUsername = $this->confirmValidGitHubUsername($member->githubUsername);
            if (is_null($validUsername)) {
                $this->issues->add(new UsernameDoesNotExist($member));

                continue;
            } elseif (Str::lower($validUsername) != Str::lower($member->githubUsername)) {
                $this->issues->add(new InvalidUsername($member, $validUsername));

                continue;
            }

            $partOfTheTeam = $gitHubMembers
                ->filter(fn($ghm) => Str::lower($ghm) == Str::lower($member->githubUsername))
                ->isNotEmpty();
            $pendingOnTheTeam = $gitHubPendingMembers
                ->filter(fn($ghm) => Str::lower($ghm) == Str::lower($member->githubUsername))
                ->isNotEmpty();
            $failedInvite = $gitHubFailedInvites
                ->filter(fn($ghm) => Str::lower($ghm) == Str::lower($member->githubUsername))
                ->isNotEmpty();
            $invited = $partOfTheTeam || $pendingOnTheTeam;

            if ($failedInvite) {
                continue; // Nothing to do here. Failed invites should automatically be cleaned out.
            } elseif (! $invited && $member->isMember) {
                $this->issues->add(new UsernameNotListedInOrganization($member));
            } elseif ($invited && ! $member->isMember) {
                $this->issues->add(new NonMemberInOrganization($member));
            }
        }

        foreach ($gitHubMembers as $gitHubMember) {
            $member = $members
                ->filter(fn($m) => Str::lower($gitHubMember) == Str::lower($m->githubUsername))
                ->first();

            if (! is_null($member)) {
                continue;  // We only care here if we COULDN'T find a matching member
            }

            $this->issues->add(new UnknownGitHubUsernameOrganization($gitHubMember));
        }
    }

    private function confirmValidGitHubUsername(?string $githubUsername): ?string
    {
        $matches = [];
        if (1 === preg_match(";(http(s)?)?://github.com/(?P<username>[\w-]+);", $githubUsername, $matches)) {
            $githubUsername = $matches['username'];
        }

        $data = $this->gitHubApi->userLookup($githubUsername);
        if (array_key_exists('login', $data)) {
            return $data['login'];
        }

        $data = $this->gitHubApi->emailLookup($githubUsername);  // In case they put their email instead of username
        if (! array_key_exists('total_count', $data)) {
            error_log(print_r($data, true));  // Probably rate limited

            return null;
        }
        if ($data['total_count'] == 1) {
            return $data['items'][0]['login'];
        }

        return null;
    }
}
