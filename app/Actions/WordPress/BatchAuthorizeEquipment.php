<?php

namespace App\Actions\WordPress;

use App\Exceptions\UnauthorizedTrainerException;
use App\Models\Customer;
use App\Models\TrainableEquipment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class BatchAuthorizeEquipment
{
    private AddUserMembership $authorizeAction;

    /**
     * Create a new action instance.
     *
     * @return void
     */
    public function __construct(
        AddUserMembership $addUserMembership
    ) {
        $this->authorizeAction = $addUserMembership;
    }

    /**
     * Execute the action.
     *
     * @param  Customer  $trainer  - The person who is submitting the authorization
     * @param  Collection<Customer>  $members  - The members to be authorized on the equipment
     * @param  Collection<TrainableEquipment>  $equipmentList  - The pieces of equipment for which the members will be authorized
     * @param  bool  $makeTrainers  - Default false. Whether the members should be authorized to trainers others on this equipment.
     *
     * @throws \Exception
     */
    public function execute(Customer $trainer, Collection $members, Collection $equipmentList, bool $makeTrainers = false)
    {
        // Validate that trainer is an authorized trainer for all equipment
        foreach ($equipmentList as $equipment) {
            if (! $trainer->hasMembership($equipment->trainer_plan_id)) {
                Log::info('BatchAuthorizeEquipment: Customer attempted to submit authorization without trainer role. '.json_encode([
                    'trainer_id' => $trainer->id,
                    'equipment_id' => $equipment->id,
                ]));

                throw new UnauthorizedTrainerException($trainer->id, $equipment->id);
            }
        }

        Log::info('BatchAuthorizeEquipment: Submitting batch: '.json_encode([
            'trainer_id' => $trainer->id,
            'member_ids' => $members->map(fn ($member) => $member->id),
            'equipment_ids' => $equipmentList->map(fn ($e) => $e->id),
            'make_trainers' => $makeTrainers,
        ]));

        $planIds = $equipmentList->map(fn ($e) => $e->user_plan_id);

        if ($makeTrainers) {
            $planIds = $planIds->concat($equipmentList->map(fn ($e) => $e->trainer_plan_id)->all());
        }

        foreach ($members->crossjoin($planIds) as [$member, $planId]) {
            if ($member->hasMembership($planId)) {
                continue;
            }
            $this->authorizeAction->onQueue()->execute($trainer->id, $member->id, $planId);
        }
    }
}
