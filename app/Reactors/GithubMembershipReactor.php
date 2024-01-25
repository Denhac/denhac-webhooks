<?php

namespace App\Reactors;

use App\Actions\GitHub\AddToGitHubTeam;
use App\Actions\GitHub\RemoveFromGitHubTeam;
use App\External\GitHub\GitHubApi;
use App\Models\Customer;
use App\StorableEvents\GitHub\GitHubUsernameUpdated;
use App\StorableEvents\Membership\MembershipActivated;
use App\StorableEvents\Membership\MembershipDeactivated;
use Spatie\EventSourcing\EventHandlers\Reactors\Reactor;

final class GithubMembershipReactor extends Reactor
{
    const MEMBERS_TEAM = 'members';

    public function onGithubUsernameUpdated(GitHubUsernameUpdated $event)
    {
        // TODO Make this handle adding/removing all teams based on the volunteer groups for this customer

        if (! is_null($event->oldUsername)) {
            /** @var GitHubApi $gitHubApi */
            $gitHubApi = app(GitHubApi::class);
            $gitHubUsers = $gitHubApi->team('members')->list()->map(fn ($ghm) => $ghm['login']);

            if ($gitHubUsers->contains($event->oldUsername)) {
                RemoveFromGitHubTeam::queue()->execute($event->oldUsername, self::MEMBERS_TEAM);
            }
        }

        // The customer can update their github without having an active subscription.
        // If they update the username, then become a member again, membership activated will take care of it
        if (! is_null($event->newUsername) && $event->isMember) {
            AddToGitHubTeam::queue()->execute($event->newUsername, self::MEMBERS_TEAM);
        }
    }

    public function onMembershipActivated(MembershipActivated $event)
    {
        /** @var Customer $customer */
        $customer = Customer::find($event->customerId);

        if (! is_null($customer->github_username)) {
            AddToGitHubTeam::queue()->execute($customer->github_username, self::MEMBERS_TEAM);
        }
    }

    public function onMembershipDeactivated(MembershipDeactivated $event)
    {
        /** @var Customer $customer */
        $customer = Customer::find($event->customerId);

        if (! is_null($customer->github_username)) {
            RemoveFromGitHubTeam::queue()->execute($customer->github_username, self::MEMBERS_TEAM);
        }
    }
}
