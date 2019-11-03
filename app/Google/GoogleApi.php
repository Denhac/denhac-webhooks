<?php

namespace App\Google;


class GoogleApi
{
    // I'm thinking this could be a class interface where I say $googleApi->groups and use it that way,
    // or $googleApi->group("members@denhac.org")->add/remove/etc.
    // Let's start by breaking the service key and private key path out into config variables.
    // Next, I'm thinking about a token issuer type class that can be used to get tokens with the correct scope automatically.
    // That class can be passed into whatever class is returned by the group method or whatever.


    /**
     * @var TokenManager
     */
    private $tokenManager;

    public function __construct(TokenManager $tokenManager)
    {
        $this->tokenManager = $tokenManager;
    }

    public function group($name) {
        return new GroupApi($this->tokenManager, $name);
    }
}

class GoogleGroupManager {
    private $INVITE_URL = "https://www.googleapis.com/admin/directory/v1/groups/test%40denhac.org/members";

    private $scopes = "https://www.googleapis.com/auth/admin.directory.group";


    public function inviteMember($email)
    {
        $this->refreshToken();

        echo $this->accessToken . "\n";

        $postArgs = array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->accessToken,
            ),
            'body' => json_encode([
                "email" => $email,
                "role" => "MEMBER",
            ]),
        );

        $response = wp_remote_post($this->INVITE_URL, $postArgs);

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);

        echo $body . "\n";

        return true;
    }

}
