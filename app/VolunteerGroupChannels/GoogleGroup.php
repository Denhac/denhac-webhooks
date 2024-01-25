<?php

namespace App\VolunteerGroupChannels;


use App\Actions\Google\AddToGroup;
use App\Actions\Google\RemoveFromGroup;
use App\Models\Customer;
use App\Models\VolunteerGroupChannel;

class GoogleGroup implements ChannelInterface
{

    function add(Customer $customer, string $channelValue): void
    {
        AddToGroup::queue()->execute($customer->email, $channelValue);
    }

    function remove(Customer $customer, string $channelValue): void
    {
        RemoveFromGroup::queue()->execute($customer->email, $channelValue);
    }

    static function getTypeKey(): string
    {
        return VolunteerGroupChannel::GOOGLE_GROUP_EMAIL;
    }

    static function removeOnMembershipLost(): bool
    {
        // There's no overall structure to Google Groups that someone can be removed from. We have to handle each group
        // individually.
        return true;
    }
}
