<?php

namespace App\Actions;

use App\Customer;
use App\Notifications\OctoPrintNewUser;
use App\OctoPrint\OctoPrintApi;
use Spatie\QueueableAction\QueueableAction;

class AddOrActivateUserToOctoPrintHost
{
    use QueueableAction;

    public $queue = 'event-sourcing';

    public function execute(Customer $customer, $host, $password = null)
    {
        if(is_null($password)) {
            $password = str_random(8);
        }

        $username = $customer->username;
        $api = app()->make(OctoPrintApi::class, ['host' => $host]);

        if(is_null($api->get_user($username))) {
            $api->add_user($username, $password);

            $customer->notify(new OctoPrintNewUser($host, $username, $password));
        } else {
            // Make sure they're an active user
            $api->update_user($username, $active = true);
        }
    }
}
