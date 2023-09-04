<?php

namespace App\Actions\OctoPrint;

use App\Customer;
use App\External\OctoPrint\OctoPrintApi;
use App\Notifications\OctoPrintNewUser;
use Illuminate\Support\Str;
use Spatie\QueueableAction\QueueableAction;

class AddOrActivateUserToOctoPrintHost
{
    use QueueableAction;

    public $queue = 'event-sourcing';

    public function execute(Customer $customer, $host, $password = null)
    {
        if (is_null($password)) {
            $password = Str::random(8);
        }

        $username = $customer->username;
        $api = app()->make(OctoPrintApi::class, ['host' => $host]);

        if (is_null($api->get_user($username))) {
            $api->add_user($username, $password);

            $customer->notify(new OctoPrintNewUser($host, $username, $password));
        } else {
            // Make sure they're an active user
            $api->update_user($username, $active = true);
        }
    }
}
