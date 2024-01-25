<?php

namespace App\VolunteerGroupChannels;


use App\Models\Customer;

interface ChannelInterface
{
    function add(Customer $customer, string $channelValue): void;

    function remove(Customer $customer, string $channelValue): void;

    static function removeOnMembershipLost(): bool;
}
