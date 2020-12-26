<?php

namespace App\Actions;

use App\Customer;
use Spatie\QueueableAction\QueueableAction;

class AddUserToOctoPrintHosts
{
    use QueueableAction;

    public $queue = 'event-sourcing';

    /**
     * @var AddOrActivateUserToOctoPrintHost
     */
    private $activateUserToOctoPrintHost;

    public function __construct(AddOrActivateUserToOctoPrintHost $activateUserToOctoPrintHost)
    {
        $this->activateUserToOctoPrintHost = $activateUserToOctoPrintHost;
    }

    public function execute(Customer $customer)
    {
        $octoprint_hosts = collect(setting('hosts'))
            ->where('type', 'octoprint')
            ->keys();

        $fake_password = str_random(8);

        foreach ($octoprint_hosts as $host) {
            $this->activateUserToOctoPrintHost
                ->onQueue()
                ->execute($customer, $host, $fake_password);
        }
    }
}
