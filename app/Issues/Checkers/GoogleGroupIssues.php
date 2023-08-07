<?php

namespace App\Issues\Checkers;


use App\Google\GmailEmailHelper;
use App\Google\GoogleApi;
use App\Issues\IssueData;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class GoogleGroupIssues implements IssueCheck
{
    private IssueData $issueData;
    private GoogleApi $googleApi;

    public function __construct(IssueData $issueData, GoogleApi $googleApi)
    {
        $this->issueData = $issueData;
        $this->googleApi = $googleApi;
    }

    public function issueTitle(): string
    {
        return "Issue with google groups";
    }

    public function getIssues(): Collection
    {
        $issues = collect();
        $members = $this->issueData->members();

        $groups = $this->googleApi->groupsForDomain('denhac.org')
            ->filter(function ($group) {
                // TODO handle excluded groups in a better way
                return $group != 'denhac@denhac.org' &&
                    $group != 'lpfmerrors@denhac.org';
            });

        $emailsToGroups = collect();

        $groups->each(function ($group) use ($issues, &$emailsToGroups) {
            $membersInGroup = $this->googleApi->group($group)->list();

            $membersInGroup->each(function ($groupMember) use ($group, &$emailsToGroups) {
                $groupMember = GmailEmailHelper::handleGmail(Str::lower($groupMember));
                $groupsForEmail = $emailsToGroups->get($groupMember, collect());
                $groupsForEmail->add($group);
                $emailsToGroups->put($groupMember, $groupsForEmail);
            });
        });

        $emailsToGroups->each(function ($groupsForEmail, $email) use ($issues, $groups, $members) {
            /** @var Collection $groupsForEmail */

            // Ignore groups of ours that are part of another group
            if ($groups->contains($email)) {
                return;
            }

            $membersForEmail = $members
                ->filter(function ($member) use ($issues, $email) {
                    /** @var Collection $memberEmails */
                    $memberEmails = $member['email'];

                    return $memberEmails->contains(Str::lower($email));
                });

            if ($membersForEmail->count() > 1) {
                $message = "More than 2 members exist for email address $email";
                $issues->add($message);

                return;
            }

            if ($membersForEmail->count() == 0) {
                $message = "No member found for email address $email in groups: {$groupsForEmail->implode(', ')}";
                $issues->add($message);

                return;
            }

            $member = $membersForEmail->first();

            if (!$member['is_member']) {
                $message = "{$member['first_name']} {$member['last_name']} with email ($email) is not an active member but is in groups: {$groupsForEmail->implode(', ')}";
                $issues->add($message);
            }
        });

        $members->each(function ($member) use ($issues, $emailsToGroups) {
            /** @var Collection $memberEmails */
            $memberEmails = $member['email'];

            if ($memberEmails->isEmpty()) {
                return;
            }

            if (!$member['is_member']) {
                return;
            }

            $memberGroupEmails = collect([
                'members@denhac.org',
                'announce@denhac.org',
            ]);

            $notInGroups = $memberGroupEmails
                ->filter(function ($groupEmail) use ($memberEmails, $emailsToGroups) {
                    foreach ($memberEmails as $memberEmail) {
                        if ($emailsToGroups->get($memberEmail)->contains($groupEmail)) {
                            return true;  // This group has this email
                        }
                    }
                    return false;  // This group has none of the members emails
            });

            if ($notInGroups->isEmpty()) {
                return;
            }

            $membersGroupsMissing = $notInGroups->implode(',');
            $membersEmailsString = $memberEmails->implode(', ');
            $message = "{$member['first_name']} {$member['last_name']} with emails ({$membersEmailsString}) is an active member but is not part of $membersGroupsMissing";
            $issues->add($message);
        });

        return $issues;
    }
}
