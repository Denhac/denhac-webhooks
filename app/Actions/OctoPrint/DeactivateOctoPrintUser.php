<?php

namespace App\Actions\OctoPrint;

use App\Actions\StaticAction;
use App\External\OctoPrint\OctoPrintApi;
use App\Models\Customer;
use Spatie\QueueableAction\QueueableAction;

class DeactivateOctoPrintUser
{
    use QueueableAction;
    use StaticAction;

    public string $queue = 'event-sourcing';

    public function execute(Customer $customer)
    {
        $octoprint_hosts = collect(setting('hosts'))
            ->where('type', 'octoprint')
            ->keys();

        foreach ($octoprint_hosts as $host) {
            $this->deactivate_user($host, $customer);
        }
    }

    private function deactivate_user($host, Customer $customer)
    {
        $username = $customer->username;
        $api = app()->make(OctoPrintApi::class, ['host' => $host]);

        if (! is_null($api->get_user($username))) {
            $api->update_user($username, $active = false);
        }
    }
}
