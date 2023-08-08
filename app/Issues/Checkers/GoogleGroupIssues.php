<?php

namespace App\Issues\Checkers;


use App\Google\GmailEmailHelper;
use App\Issues\IssueData;
use App\Issues\Types\GoogleGroups\ActiveMemberNotInGroups;
use App\Issues\Types\GoogleGroups\NoMemberFoundForEmail;
use App\Issues\Types\GoogleGroups\NotActiveMemberButInGroups;
use App\Issues\Types\GoogleGroups\TwoMembersSameEmail;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class GoogleGroupIssues implements IssueCheck
{
    use IssueCheckTrait;

    private IssueData $issueData;

    public function __construct(IssueData $issueData)
    {
        $this->issueData = $issueData;
    }

    public function issueTitle(): string
    {
        return "Issue with google groups";
    }

    public function generateIssues(): void
    {
        $members = $this->issueData->members();

        $groups = $this->issueData->googleGroups()
            ->filter(function ($group) {
                // TODO handle excluded groups in a better way
                return $group != 'denhac@denhac.org' &&
                    $group != 'lpfmerrors@denhac.org';
            });

        $emailsToGroups = collect();

        $groups->each(function ($group) use (&$emailsToGroups) {
            $membersInGroup = $this->issueData->googleGroupMembers($group);

            $membersInGroup->each(function ($groupMember) use ($group, &$emailsToGroups) {
                $groupMember = GmailEmailHelper::handleGmail(Str::lower($groupMember));
                $groupsForEmail = $emailsToGroups->get($groupMember, collect());
                $groupsForEmail->add($group);
                $emailsToGroups->put($groupMember, $groupsForEmail);
            });
        });

        $emailsToGroups->each(function ($groupsForEmail, $email) use ($groups, $members) {
            /** @var Collection $groupsForEmail */

            // Ignore groups of ours that are part of another group
            if ($groups->contains($email)) {
                return;
            }

            $membersForEmail = $members
                ->filter(function ($member) use ($email) {
                    /** @var Collection $memberEmails */
                    $memberEmails = $member['email'];

                    return $memberEmails->contains(Str::lower($email));
                });

            if ($membersForEmail->count() > 1) {
                $this->issues->add(new TwoMembersSameEmail($email, $membersForEmail));

                return;
            }

            if ($membersForEmail->count() == 0) {
                $this->issues->add(new NoMemberFoundForEmail($email, $groupsForEmail));

                return;
            }

            $member = $membersForEmail->first();

            if (!$member['is_member']) {
                $this->issues->add(new NotActiveMemberButInGroups($member, $email, $groupsForEmail));
            }
        });

        $members->each(function ($member) use ($emailsToGroups) {
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
                        $groupsForThisEmail = $emailsToGroups->get($memberEmail, collect());
                        if ($groupsForThisEmail->contains($groupEmail)) {
                            return false;  // This group has this email
                        }
                    }
                    return true;  // This group has none of the members emails
                });

            if ($notInGroups->isEmpty()) {
                return;
            }

            $this->issues->add(new ActiveMemberNotInGroups($member, $notInGroups));
        });
    }
}
