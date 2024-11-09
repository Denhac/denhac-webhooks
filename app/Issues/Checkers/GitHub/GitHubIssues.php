<?php

namespace App\Issues\Checkers\GitHub;

use App\DataCache\AggregateCustomerData;
use App\DataCache\GitHubFailedInvites;
use App\DataCache\GitHubMembers;
use App\DataCache\GitHubPendingMembers;
use App\DataCache\MemberData;
use App\External\GitHub\GitHubApi;
use App\External\HasApiProgressBar;
use App\Issues\Checkers\IssueCheck;
use App\Issues\Checkers\IssueCheckTrait;
use App\Issues\Types\GitHub\InvalidUsername;
use App\Issues\Types\GitHub\NonMemberInOrganization;
use App\Issues\Types\GitHub\UnknownGitHubUsernameOrganization;
use App\Issues\Types\GitHub\UsernameDoesNotExist;
use App\Issues\Types\GitHub\UsernameNotListedInOrganization;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class GitHubIssues implements IssueCheck
{
    use HasApiProgressBar;
    use IssueCheckTrait;

    public function __construct(
        private readonly GitHubApi $gitHubApi,
        private readonly AggregateCustomerData $aggregateCustomerData,
        private readonly GitHubMembers $gitHubMembers,
        private readonly GitHubPendingMembers $gitHubPendingMembers,
        private readonly GitHubFailedInvites $gitHubFailedInvites
    ) {}

    protected function generateIssues(): void
    {
        $gitHubMembers = $this->gitHubMembers->get()->map(fn ($ghm) => $ghm['login']);
        $gitHubPendingMembers = $this->gitHubPendingMembers->get()->map(fn ($ghm) => $ghm['login']);
        $gitHubFailedInvites = $this->gitHubFailedInvites->get()->map(fn ($ghm) => $ghm['login']);
        /** @var Collection<MemberData> $members */
        $members = $this->aggregateCustomerData->get()->filter(fn ($member) => ! is_null($member->githubUsername));

        $progress = $this->apiProgress('Checking GitHub users');
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
                ->filter(fn ($ghm) => Str::lower($ghm) == Str::lower($member->githubUsername))
                ->isNotEmpty();
            $pendingOnTheTeam = $gitHubPendingMembers
                ->filter(fn ($ghm) => Str::lower($ghm) == Str::lower($member->githubUsername))
                ->isNotEmpty();
            $failedInvite = $gitHubFailedInvites
                ->filter(fn ($ghm) => Str::lower($ghm) == Str::lower($member->githubUsername))
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
                ->filter(fn ($m) => Str::lower($gitHubMember) == Str::lower($m->githubUsername))
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
        if (preg_match(";(http(s)?)?://github.com/(?P<username>[\w-]+);", $githubUsername, $matches) === 1) {
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
