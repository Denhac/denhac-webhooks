<?php

namespace App\VolunteerGroupChannels;


use App\Actions\Slack\AddToChannel;
use App\Actions\Slack\RemoveFromChannel;
use App\Models\Customer;

class SlackChannel implements ChannelInterface
{
    function add(Customer $customer, string $channelValue): void
    {
        AddToChannel::queue()->execute($customer->slack_id, $channelValue);
    }

    function remove(Customer $customer, string $channelValue): void
    {
        RemoveFromChannel::queue()->execute($customer->slack_id, $channelValue);
    }

    static function removeOnMembershipLost(): bool
    {
        // User is demoted to single channel guest, no need to remove them here from individual channels
        return false;
    }
}
