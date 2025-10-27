<?php

namespace App\Issues\Types\InternalConsistency;

use App\Issues\Types\ICanFixThem;
use App\Issues\Types\IssueBase;
use App\Models\TrainableEquipment;

class TrainableEquipmentMissingAssociatedPlans extends IssueBase
{
    use ICanFixThem;

    private readonly bool $missingUserPlan;
    private readonly bool $missingTrainerPlan;

    public function __construct(
        private readonly TrainableEquipment $equipment,
                                            ...$missingIds
    )
    {
        $this->missingUserPlan = in_array($this->equipment->user_plan_id, $missingIds);
        $this->missingTrainerPlan = in_array($this->equipment->trainer_plan_id, $missingIds);
    }

    public static function getIssueNumber(): int
    {
        return 216;  // auto-generated based on namespace and existing issues
    }

    public static function getIssueTitle(): string
    {
        return "Internal Consistency: Trainable equipment missing associated plans";
    }

    public function getIssueText(): string
    {
        $plansTextList = [];
        if ($this->missingUserPlan) {
            $plansTextList[] = "user plan ({$this->equipment->user_plan_id})";
        }
        if ($this->missingTrainerPlan) {
            $plansTextList[] = "trainer plan ({$this->equipment->trainer_plan_id})";
        }

        // Technically we can only have 2 plans in this list currently, but better to write it now while I'm thinking
        // about it.
        if (count($plansTextList) == 1) {
            $plansText = $plansTextList[0];
        } else if (count($plansTextList) == 2) {
            $plansText = implode(" and ", $plansTextList);
        } else {
            $plansText = (
                implode(", ", array_slice($plansTextList, 0, count($plansTextList) - 1))
                . ", and "  // Oxford comma, mission critical.
                . $plansTextList[count($plansTextList) - 1]
            );
        }

        return "{$this->equipment->name} has missing plans $plansText";
    }

    public function fix(): bool
    {
        return $this->issueFixChoice()
            ->option("Delete Trainable Equipment", function() {
                // TODO Delete the used plan. Currently the API doesn't support deleting a membership.

                // if we're not missing one of the plans, we need to throw an exception so the user can go manually
                // delete the plans still in progress. Otherwise they'll just linger.
                $missingAllPlans = $this->missingUserPlan && $this->missingTrainerPlan;
                if(! $missingAllPlans) {
                    $plans = [$this->equipment->user_plan_id, $this->equipment->trainer_plan_id];
                    $plans = implode(", ", $plans);
                    $msg = "One of the plans related to this equipment still exists and must manually be deleted: $plans";
                    throw new \Exception($msg);
                }

                $this->equipment->delete();
            })
            ->run();
    }
}
