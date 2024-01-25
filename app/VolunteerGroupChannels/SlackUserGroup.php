<?php

namespace App\VolunteerGroupChannels;


use App\Actions\Slack\AddToUserGroup;
use App\Actions\Slack\RemoveFromUserGroup;
use App\Models\Customer;
use App\Models\VolunteerGroupChannel;

class SlackUserGroup implements ChannelInterface
{
    function add(Customer $customer, string $channelValue): void
    {
        AddToUserGroup::queue()->execute($customer->slack_id, $channelValue);
    }

    function remove(Customer $customer, string $channelValue): void
    {
        RemoveFromUserGroup::queue()->execute($customer->slack_id, $channelValue);
    }

    static function getTypeKey(): string
    {
        return VolunteerGroupChannel::SLACK_USER_GROUP_ID;
    }

    static function removeOnMembershipLost(): bool
    {
        // Demoting someone to a single channel guest can still keep them in a user group, so we remove them manually
        return true;
    }
}
