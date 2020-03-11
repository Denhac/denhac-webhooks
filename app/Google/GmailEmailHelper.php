<?php

namespace App\Google;


class GmailEmailHelper
{
    /**
     * Modify emails in the form foo+bar@gmail.com to foo@gmail.com
     * Do not modify emails in the form of foo+bar@test.com
     *
     * Basically, for gmail based addresses, we can't add a plus based email to a group.
     *
     * @param $email
     * @return string
     */
    public static function removePlusInGmail($email)
    {
        return preg_replace("#(.+)\+.*@(gmail.com)#", '$1@$2', $email);
    }
}
