<?php

namespace App\Issues\Checkers\GoogleGroups;

use App\DataCache\AggregateCustomerData;
use App\DataCache\GoogleGroupMembers;
use App\DataCache\GoogleGroups;
use App\DataCache\MemberData;
use App\External\Google\GmailEmailHelper;
use App\Issues\Checkers\IssueCheck;
use App\Issues\Checkers\IssueCheckTrait;
use App\Issues\Types\GoogleGroups\ActiveMemberNotInGroups;
use App\Issues\Types\GoogleGroups\NoMemberFoundForEmail;
use App\Issues\Types\GoogleGroups\NoMembersInGroup;
use App\Issues\Types\GoogleGroups\NotActiveMemberButInGroups;
use App\Issues\Types\GoogleGroups\TwoMembersSameEmail;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class GoogleGroupIssues implements IssueCheck
{
    use IssueCheckTrait;

    public function __construct(
        private readonly AggregateCustomerData $aggregateCustomerData,
        private readonly GoogleGroups $googleGroups,
        private readonly GoogleGroupMembers $googleGroupMembers
    ) {}

    protected function generateIssues(): void
    {
        /** @var Collection<MemberData> $members */
        $members = $this->aggregateCustomerData->get();

        $groups = $this->googleGroups->get()
            ->filter(function ($group) {
                // TODO handle excluded groups in a better way
                return $group != 'denhac@denhac.org' &&
                    $group != 'lpfmerrors@denhac.org';
            });

        $emailsToGroups = collect();

        $groups->each(function ($group) use (&$emailsToGroups) {
            /** @var Collection $membersInGroup */
            $membersInGroup = $this->googleGroupMembers->get($group);

            if($membersInGroup->isEmpty()) {
                $this->issues->add(new NoMembersInGroup($group));
                return;
            }

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
                    /** @var MemberData $member */
                    $memberEmails = $member->emails;

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

            /** @var MemberData $member */
            $member = $membersForEmail->first();

            if (! $member->isMember) {
                $this->issues->add(new NotActiveMemberButInGroups($member, $email, $groupsForEmail));
            }
        });

        $members->each(function ($member) use ($emailsToGroups) {
            /** @var MemberData $member */
            $memberEmails = $member->emails;

            if ($memberEmails->isEmpty()) {
                return;
            }

            if (! $member->isMember) {
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
