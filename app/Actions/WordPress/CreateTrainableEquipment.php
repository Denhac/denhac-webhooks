<?php

namespace App\Actions\WordPress;

use App\External\WooCommerce\Api\WooCommerceApi;
use App\Models\Customer;
use App\Models\TrainableEquipment;
use Spatie\QueueableAction\QueueableAction;

class CreateTrainableEquipment
{
    use QueueableAction;

    public function __construct(
        private readonly WooCommerceApi $wooCommerceApi,
        private readonly AddUserMembership $addUserMembership,
    ) {}

    public function execute(
        string $equipmentName,
        Customer $submittingUser,
        Customer $initialTrainer,
        ?string $userSlackChannel,
        ?string $trainerSlackChannel,
    ): void {
        $responseTrainer = $this->wooCommerceApi->denhac->createUserPlan(
            "$equipmentName Trainer",
            $submittingUser->id
        );
        $trainerPlanId = $responseTrainer['id'];

        $responseUser = $this->wooCommerceApi->denhac->createUserPlan(
            "$equipmentName User",
            $submittingUser->id
        );
        $userPlanId = $responseUser['id'];

        $trainableEquipmentData = [
            'name' => $equipmentName,
            'user_plan_id' => $userPlanId,
            'trainer_plan_id' => $trainerPlanId,
        ];

        if (! empty($userSlackChannel)) {
            $trainableEquipmentData['user_slack_id'] = $userSlackChannel;
        }
        if (! empty($trainerSlackChannel)) {
            $trainableEquipmentData['trainer_slack_id'] = $trainerSlackChannel;
        }

        TrainableEquipment::create($trainableEquipmentData);

        $this->addUserMembership->execute($submittingUser->id, $initialTrainer->id, $userPlanId);
        $this->addUserMembership->execute($submittingUser->id, $initialTrainer->id, $trainerPlanId);
    }
}
