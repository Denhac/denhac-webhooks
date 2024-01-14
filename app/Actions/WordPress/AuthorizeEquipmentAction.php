<?php

namespace App\Actions\WordPress;

use App\External\WooCommerce\Api\WooCommerceApi;
use Spatie\QueueableAction\QueueableAction;
use Illuminate\Support\Facades\Log;

class AuthorizeEquipmentAction
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
    public function execute($trainerId, $memberId, $planId)
    {
        $this->wpApi->members->addMembership($memberId, $planId);
        Log::info('AuthorizeEquipmentAction: Customer '.$trainerId.' authorized Customer '.$memberId.' for equipment plan id '.$planId);
    }
}
