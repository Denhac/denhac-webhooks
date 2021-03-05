<?php

namespace App\Slack;


use Jeremeamia\Slack\BlockKit\Slack;
use Jeremeamia\Slack\BlockKit\Surfaces\Message;

class CommonResponses
{
    public static function unrecognizedUser(): Message
    {
        return Slack::newMessage()
            ->text("I don't recognize you. If you're a member in good standing, please contact access@denhac.org.");
    }

    public static function memberInGoodStanding(): Message
    {
        return Slack::newMessage()
            ->text("I recognize you but you don't appear to be a member in good standing. If you think this is a mistake, please contact access@denhac.org.");
    }
}
