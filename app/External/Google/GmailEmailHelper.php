<?php

namespace App\External\Google;

class GmailEmailHelper
{
    /**
     * Modify emails in the form foo+bar@gmail.com to foo@gmail.com
     * Do not modify emails in the form of foo+bar@test.com.
     *
     * Basically, for gmail based addresses, we can't add a plus based email to a group.
     *
     * @param $email
     * @return string
     */
    public static function handleGmail($email)
    {
        $email = preg_replace("#(.+)\+.*@(gmail.com)#", '$1@$2', $email);

        preg_match("#(.+)@gmail.com#", $email, $matches);

        if(! empty($matches)) {
            $email = str_replace('.', '', $matches[1]) . "@gmail.com";
        }

        return $email;
    }
}
