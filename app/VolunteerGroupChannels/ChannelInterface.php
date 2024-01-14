<?php

namespace App\VolunteerGroupChannels;


use App\Models\Customer;

interface ChannelInterface
{
    function add(Customer $customer);

    function remove(Customer $customer);
}
