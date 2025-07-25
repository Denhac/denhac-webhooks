<?php

namespace App\External\Slack;

class MembershipType
{
    public const string SINGLE_CHANNEL_GUEST = "ultra_restricted";
    public const string MULTI_CHANNEL_GUEST = "restricted";
    public const string FULL_USER = "regular";
}
