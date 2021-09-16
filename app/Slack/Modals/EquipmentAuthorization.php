<?php

namespace App\Slack\Modals;

use App\Customer;
use App\Http\Requests\SlackRequest;
use App\Slack\BlockActions\BlockActionInterface;
use App\Slack\BlockActions\RespondsToBlockActions;
use App\Slack\SlackOptions;
use App\TrainableEquipment;
use Illuminate\Support\Facades\Log;
use SlackPhp\BlockKit\Kit;
use SlackPhp\BlockKit\Partials\Option;
use SlackPhp\BlockKit\Surfaces\Modal;

class EquipmentAuthorization implements ModalInterface
{
    use ModalTrait;
    use RespondsToBlockActions;

    private const EQUIPMENT_DROPDOWN = 'equipment-dropdown';
    private const PERSON_DROPDOWN = 'person-dropdown';
    private const TRAINER_CHECK = 'trainer-check';
    private const USER_CHECK = 'user-check';

    private Modal $modalView;

    public function __construct()
    {
        $this->setUpModalCommon();

        $this->noEquipment();
        $this->noPerson();
    }

    private function setUpModalCommon() {
        $this->modalView = Kit::newModal()
            ->callbackId(self::callbackId())
            ->title('Equipment Authorization')
            ->clearOnClose(true)
            ->close('Cancel')
            ->submit('Submit');

        $this->modalView->newInput()
            ->dispatchAction()
            ->blockId(self::EQUIPMENT_DROPDOWN)
            ->label("Equipment")
            ->newSelectMenu()
            ->forExternalOptions()
            ->actionId(self::EQUIPMENT_DROPDOWN)
            ->placeholder("Select equipment")
            ->minQueryLength(0);

        $this->modalView->newInput()
            ->dispatchAction()
            ->blockId(self::PERSON_DROPDOWN)
            ->label("Person")
            ->newSelectMenu()
            ->forExternalOptions()
            ->actionId(self::PERSON_DROPDOWN)
            ->placeholder("Select a member")
            ->minQueryLength(2);
    }

    public static function callbackId(): string
    {
        return 'equipment-authorization-modal';
    }

    public static function handle(SlackRequest $request)
    {
        Log::info("Equipment authorization");
        Log::info(print_r($request->payload(), true));

        return (new SuccessModal())->update();
    }

    /**
     * @return BlockActionInterface[]
     */
    public static function getBlockActions(): array
    {
        return [
            self::blockActionUpdate(self::EQUIPMENT_DROPDOWN),
            self::blockActionUpdate(self::PERSON_DROPDOWN),
        ];
    }

    public static function getOptions(SlackRequest $request)
    {
        Log::info("Equipment auth options request");
        Log::info(print_r($request->payload(), true));

        $blockId = $request->payload()['block_id'];

        if($blockId == self::EQUIPMENT_DROPDOWN) {
            $options = SlackOptions::new();
            $trainingList = $request->customer()->equipmentTrainer;

            foreach ($trainingList as $equipment) {
                /** @var TrainableEquipment $equipment */
                $options->option($equipment->name, "equipment-{$equipment->id}");
            }
            return $options;
        } else if($blockId == self::PERSON_DROPDOWN) {
            return SelectAMemberModal::getOptions($request);
        }

        return [];
    }

    public function jsonSerialize()
    {
        return $this->modalView->jsonSerialize();
    }

    static function onBlockAction(SlackRequest $request)
    {
        Log::info("onBlockAction is called");
        $modal = new EquipmentAuthorization();
        $modal->setUpModalCommon();

        $state = self::getStateValues($request);
        Log::info("State: ".print_r($state, true));
        $equipmentValue = $state[self::EQUIPMENT_DROPDOWN][self::EQUIPMENT_DROPDOWN] ?? null;
        $personValue = $state[self::PERSON_DROPDOWN][self::PERSON_DROPDOWN] ?? null;

        Log::info("EquipmentValue: ".$equipmentValue);
        Log::info("PersonValue: ".$personValue);

        if(is_null($equipmentValue)) {
            $modal->noEquipment();
        }

        if(is_null($personValue)) {
            $modal->noPerson();
        }

        if(!is_null($equipmentValue) && !is_null($personValue)) {
            $equipmentId = str_replace('equipment-', '', $equipmentValue);
            $personId = str_replace('customer-', '', $personValue);
            /** @var Customer $person */
            $person = Customer::whereWooId($personId)->first();
            $name = "{$person->first_name} {$person->last_name}";

            /** @var TrainableEquipment $equipment */
            $equipment = TrainableEquipment::find($equipmentId);
            if($person->hasMembership($equipment->userPlanId)) {
                $modal->modalView->newSection()
                    ->plainText(":white_check_mark: $name is already an authorized user.");
            } else {
                $option = Option::new("User")
                    ->description("The person can use the equipment.")
                    ->value('true');
                $modal->modalView->newActions()
                    ->blockId(self::USER_CHECK)
                    ->newCheckboxes()
                    ->actionId(self::USER_CHECK)
                    ->addOption($option, true);
            }

            if($person->hasMembership($equipment->trainerPlanId)) {
                $modal->modalView->newSection()
                    ->plainText(":white_check_mark: $name is already an authorized trainer.");
            } else {
                $option = Option::new("Trainer")
                    ->description("The person can train others to use the equipment and add new trainers.")
                    ->value('true');
                $modal->modalView->newActions()
                    ->blockId(self::TRAINER_CHECK)
                    ->newCheckboxes()
                    ->actionId(self::TRAINER_CHECK)
                    ->addOption($option, false);
            }
        }

        return $modal->update();
    }

    private function noEquipment()
    {
        $this->modalView->newSection()
            ->mrkdwnText("Please select the Equipment you're training for.");
    }

    private function noPerson()
    {
        $this->modalView->newSection()
            ->mrkdwnText("Please select who you're authorization.");
    }
}
