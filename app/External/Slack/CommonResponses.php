<?php

namespace App\External\Slack;


class CommonResponses
{
    public static function unrecognizedUser(): string
    {
        return "I don't recognize you. If you're a member in good standing, please contact access@denhac.org.";
    }

    public static function notAMemberInGoodStanding(): string
    {
        return "I recognize you but you don't appear to be a member in good standing. If you think this is a mistake, please contact access@denhac.org.";
    }
}
