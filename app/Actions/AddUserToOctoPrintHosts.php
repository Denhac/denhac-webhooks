<?php

namespace App\Actions;

use App\Customer;
use Illuminate\Support\Str;
use Spatie\QueueableAction\QueueableAction;

class AddUserToOctoPrintHosts
{
    use QueueableAction;
    use StaticAction;

    public string $queue = 'event-sourcing';

    private AddOrActivateUserToOctoPrintHost $activateUserToOctoPrintHost;

    public function __construct(AddOrActivateUserToOctoPrintHost $activateUserToOctoPrintHost)
    {
        $this->activateUserToOctoPrintHost = $activateUserToOctoPrintHost;
    }

    public function execute(Customer $customer)
    {
        $octoprint_hosts = collect(setting('hosts'))
            ->where('type', 'octoprint')
            ->keys();

        $fake_password = Str::random(8);

        foreach ($octoprint_hosts as $host) {
            $this->activateUserToOctoPrintHost
                ->onQueue()
                ->execute($customer, $host, $fake_password);
        }
    }
}
