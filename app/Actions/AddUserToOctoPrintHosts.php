<?php

namespace App\Actions;

use App\Customer;
use App\Notifications\OctoPrintNewUser;
use App\OctoPrint\OctoPrintApi;
use Spatie\QueueableAction\QueueableAction;

class AddUserToOctoPrintHosts
{
    use QueueableAction;

    public function execute(Customer $customer)
    {
        $octoprint_hosts = collect(setting('hosts'))
            ->where('type', 'octoprint')
            ->keys();

        $fake_password = str_random(8);

        foreach ($octoprint_hosts as $host) {
            $this->invite($host, $customer, $fake_password);
        }
    }

    private function invite($host, Customer $customer, $password)
    {
        $username = $customer->username;
        $api = app()->make(OctoPrintApi::class, ['host' => $host]);

        if(is_null($api->get_user($username))) {
            $api->add_user($username, $password);

            $customer->notify(new OctoPrintNewUser($host, $username, $password));
        }
    }
}
