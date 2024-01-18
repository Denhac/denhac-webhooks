<?php

namespace App\Actions\WordPress;

use App\Models\Customer;
use App\Actions\WordPress\AddUserMembershipo;
use Illuminate\Support\Facades\Log;

class BatchAuthorizeEquipmentAction
{
    /**
     * Create a new action instance.
     *
     * @return void
     */
    public function __construct(
        AddUserMembership $addUserMembership 
    )
    {
        $this->authorizeAction = $addUserMembership;
    }

    /**
     * Execute the action.
     * @param Customer $trainer - The person who is submitting the authorization
     * @param Collection<Customer> $members - The members to be authorized on the equipment
     * @param Collection<TrainableEquipment> $equipment - The pieces of equipment for which the members will be authorized
     * @param Boolean $makeTrainers - Default false. Whether the members should be authorized to trainers others on this equipment.
     * @return mixed
     */
    public function execute(Customer $trainer, $members, $equipment, $makeTrainers=false)
    {
        // Validate that trainer is an authorized trainer for all equipment
        foreach($equipment as $e) {
            if (!$trainer->hasMembership($e->trainer_plan_id)) {
                Log::info('BatchAuthorizeEquipment: Customer attempted to submit authorization without trainer role. '.json_encode([
                    'trainer_id' => $trainer->id,
                    'equipment_id' => $e->id
                ]));

                throw new \Exception('NotAuthorized');
            }
        }

        Log::info('BatchAuthorizeEquipment: Submitting batch: '.json_encode([
            'trainer_id' => $trainer->id,
            'member_ids' => $members->map(fn($member) => $member->id),
            'equipment_ids' => $equipment->map(fn($e) => $e->id),
            'make_trainers' => $makeTrainers
        ]));

        $planIds = $equipment->map(fn($e) => $e->user_plan_id);

        if ($makeTrainers) {
            $planIds = $planIds->concat($equipment->map(fn ($e) => $e->trainer_plan_id));
        }

        foreach($members->crossjoin($planIds) as [$member, $planId]){
            if ($member->hasMembership($planId)) {
                continue;
            }
            $this->authorizeAction->onQueue()->execute($trainer->id, $member->id, $planId);
        }
        return 'ok';
    }
}
