<?php

namespace App\Actions\WordPress;

use App\External\WooCommerce\Api\WooCommerceApi;
use Spatie\QueueableAction\QueueableAction;
use Illuminate\Support\Facades\Log;

class AddUserMembership
{
    use QueueableAction;

    /**
     * Create a new action instance.
     *
     * @return void
     */
    public function __construct(WooCommerceApi $wpApi)
    {
        $this->wpApi = $wpApi;
    }

    /**
     * Execute the action.
     *
     * @return mixed
     */
    public function execute($actorId, $memberId, $planId)
    {
        $this->wpApi->members->addMembership($memberId, $planId);
        Log::info('AddUserMembership: Customer '.$actorId.' granted user plan id '.$planId.' to Customer '.$memberId);
    }
}
