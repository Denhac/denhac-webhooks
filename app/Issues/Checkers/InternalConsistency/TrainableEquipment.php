<?php

namespace App\Issues\Checkers\InternalConsistency;

use App\DataCache\WooCommerceMembershipPlans;
use App\Issues\Checkers\IssueCheck;
use App\Issues\Checkers\IssueCheckTrait;
use App\Issues\Types\InternalConsistency\TrainableEquipmentMissingAssociatedPlans;
use Illuminate\Support\Collection;

class TrainableEquipment implements IssueCheck
{
    use IssueCheckTrait;

    public function __construct(
        private readonly WooCommerceMembershipPlans $membershipPlans
    ) {}

    protected function generateIssues(): void
    {
        /** @var Collection $plans */
        $plans = $this->membershipPlans->get()->mapWithKeys(function ($item) {
            return [$item['id'] => $item];
        });
        $equipmentList = \App\Models\TrainableEquipment::all();

        foreach ($equipmentList as $equipment) {
            $missingIds = [];

            if (! $plans->has($equipment->user_plan_id)) {
                $missingIds[] = $equipment->user_plan_id;
            }

            if (! $plans->has($equipment->trainer_plan_id)) {
                $missingIds[] = $equipment->trainer_plan_id;
            }

            if (count($missingIds) == 0) {
                continue;
            }

            $this->issues->add(
                new TrainableEquipmentMissingAssociatedPlans($equipment, ...$missingIds)
            );
        }
    }
}
