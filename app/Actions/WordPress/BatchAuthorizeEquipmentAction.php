<?php

namespace App\Actions\WordPress;

use App\Models\Customer;
use App\Actions\WordPress\AuthorizeEquipmentAction;

class BatchAuthorizeEquipmentAction
{
    /**
     * Create a new action instance.
     *
     * @return void
     */
    public function __construct(
        AuthorizeEquipmentAction $authorizeEquipmentAction 
    )
    {
        $this->authorizeAction = $authorizeEquipmentAction;
    }

    /**
     * Execute the action.
     *
     * @return mixed
     */
    public function execute(Customer $trainer, $members, $planIds)
    {
        foreach($members->crossjoin($planIds) as [$member, $planId]){
            $this->authorizeAction->onQueue()->execute($trainer->id, $member->id, $planId);
        }
        return 'ok';
    }
}
