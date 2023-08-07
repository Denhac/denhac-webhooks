<?php

namespace App\Issues\Checkers;


trait SlackMembershipHelper
{
    private function isFullSlackUser($slackUser): bool
    {
        if (
            (array_key_exists('deleted', $slackUser) && $slackUser['deleted']) ||
            (array_key_exists('is_restricted', $slackUser) && $slackUser['is_restricted']) ||
            (array_key_exists('is_ultra_restricted', $slackUser) && $slackUser['is_ultra_restricted'])
        ) {
            return false;
        }

        return true;
    }
}
