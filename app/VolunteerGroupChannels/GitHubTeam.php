<?php

namespace App\VolunteerGroupChannels;


use App\Actions\GitHub\AddToGitHubTeam;
use App\Actions\GitHub\RemoveFromGitHubTeam;
use App\Models\Customer;
use App\Models\VolunteerGroupChannel;

class GitHubTeam implements ChannelInterface
{

    function add(Customer $customer, string $channelValue): void
    {
        if (is_null($customer->github_username)) {
            return;
        }

        AddToGitHubTeam::queue()->execute($customer->github_username, $channelValue);
    }

    function remove(Customer $customer, string $channelValue): void
    {
        if (is_null($customer->github_username)) {
            return;
        }

        RemoveFromGitHubTeam::queue()->execute($customer->github_username, $channelValue);
    }

    static function getTypeKey(): string
    {
        return VolunteerGroupChannel::GITHUB_TEAM_NAME;
    }

    static function removeOnMembershipLost(): bool
    {
        // Removing from the organization may be sufficient to remove from all teams
        // TODO Actually remove from the organization instead of from the team
        return false;
    }
}
