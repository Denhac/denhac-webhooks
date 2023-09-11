<?php

namespace App\External\Slack\Modals;

use App\Models\Customer;
use App\External\Slack\BlockActions\BlockActionInterface;
use App\External\Slack\BlockActions\RespondsToBlockActions;
use App\External\Slack\SlackOptions;
use App\External\WooCommerce\Api\WooCommerceApi;
use App\Http\Requests\SlackRequest;
use App\Models\TrainableEquipment;
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

    private function setUpModalCommon()
    {
        $this->modalView = Kit::newModal()
            ->callbackId(self::callbackId())
            ->title('Equipment Authorization')
            ->clearOnClose(true)
            ->close('Cancel')
            ->submit('Submit');

        $this->modalView->newInput()
            ->dispatchAction()
            ->blockId(self::EQUIPMENT_DROPDOWN)
            ->label('Equipment')
            ->newSelectMenu()
            ->forExternalOptions()
            ->actionId(self::EQUIPMENT_DROPDOWN)
            ->placeholder('Select equipment')
            ->minQueryLength(0);

        $this->modalView->newInput()
            ->dispatchAction()
            ->blockId(self::PERSON_DROPDOWN)
            ->label('Person')
            ->newSelectMenu()
            ->forExternalOptions()
            ->actionId(self::PERSON_DROPDOWN)
            ->placeholder('Select a member')
            ->minQueryLength(2);
    }

    public static function callbackId(): string
    {
        return 'equipment-authorization-modal';
    }

    public static function handle(SlackRequest $request)
    {
        Log::info('Equipment authorization');
        Log::info(print_r($request->payload(), true));

        $state = self::getStateValues($request);
        $equipmentValue = $state[self::EQUIPMENT_DROPDOWN][self::EQUIPMENT_DROPDOWN] ?? null;
        $personValue = $state[self::PERSON_DROPDOWN][self::PERSON_DROPDOWN] ?? null;
        $makeUser = ! is_null($state[self::USER_CHECK][self::USER_CHECK] ?? null);
        $makeTrainer = ! is_null($state[self::TRAINER_CHECK][self::TRAINER_CHECK] ?? null);

        $equipmentId = str_replace('equipment-', '', $equipmentValue);
        $personId = str_replace('customer-', '', $personValue);

        /** @var Customer $person */
        $person = Customer::find($personId);

        /** @var TrainableEquipment $equipment */
        $equipment = TrainableEquipment::find($equipmentId);

        /** @var WooCommerceApi $api */
        $api = app(WooCommerceApi::class);

        if ($makeUser) {
            $api->members->addMembership($person->id, $equipment->user_plan_id);
        }

        if ($makeTrainer) {
            $api->members->addMembership($person->id, $equipment->trainer_plan_id);
        }

        if (! $makeTrainer && ! $makeUser) {
            return (new FailureModal('Neither user nor trainer appear to have been selected. Try again?'))
                ->update();
        }

        // TODO actually check error before sending success
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
            self::blockActionDoNothing(self::USER_CHECK),
            self::blockActionDoNothing(self::TRAINER_CHECK),
        ];
    }

    public static function getOptions(SlackRequest $request)
    {
        Log::info('Equipment auth options request');
        Log::info(print_r($request->payload(), true));

        $blockId = $request->payload()['block_id'];

        if ($blockId == self::EQUIPMENT_DROPDOWN) {
            $options = SlackOptions::new();
            $trainingList = $request->customer()->equipmentTrainer;

            foreach ($trainingList as $equipment) {
                /** @var TrainableEquipment $equipment */
                $options->option($equipment->name, "equipment-{$equipment->id}");
            }

            return $options;
        } elseif ($blockId == self::PERSON_DROPDOWN) {
            return SelectAMemberModal::getOptions($request);
        }

        return [];
    }

    public function jsonSerialize()
    {
        return $this->modalView->jsonSerialize();
    }

    public static function onBlockAction(SlackRequest $request)
    {
        $modal = new EquipmentAuthorization();
        $modal->setUpModalCommon();

        $state = self::getStateValues($request);
        $equipmentValue = $state[self::EQUIPMENT_DROPDOWN][self::EQUIPMENT_DROPDOWN] ?? null;
        $personValue = $state[self::PERSON_DROPDOWN][self::PERSON_DROPDOWN] ?? null;

        if (is_null($equipmentValue)) {
            $modal->noEquipment();
        }

        if (is_null($personValue)) {
            $modal->noPerson();
        }

        if (! is_null($equipmentValue) && ! is_null($personValue)) {
            $equipmentId = str_replace('equipment-', '', $equipmentValue);
            $personId = str_replace('customer-', '', $personValue);
            /** @var Customer $person */
            $person = Customer::find($personId);
            $name = "{$person->first_name} {$person->last_name}";

            /** @var TrainableEquipment $equipment */
            $equipment = TrainableEquipment::find($equipmentId);
            if ($person->hasMembership($equipment->user_plan_id)) {
                $modal->modalView->newSection()
                    ->plainText(":white_check_mark: $name is already an authorized user.");
            } else {
                $option = Option::new('User')
                    ->description('The person can use the equipment.')
                    ->value('true');
                $modal->modalView->newActions()
                    ->blockId(self::USER_CHECK)
                    ->newCheckboxes()
                    ->actionId(self::USER_CHECK)
                    ->addOption($option, true);
            }

            if ($person->hasMembership($equipment->trainer_plan_id)) {
                $modal->modalView->newSection()
                    ->plainText(":white_check_mark: $name is already an authorized trainer.");
            } else {
                $option = Option::new('Trainer')
                    ->description('The person can train others to use the equipment and add new trainers.')
                    ->value('true');
                $modal->modalView->newActions()
                    ->blockId(self::TRAINER_CHECK)
                    ->newCheckboxes()
                    ->actionId(self::TRAINER_CHECK)
                    ->addOption($option, false);
            }
        }

        return $modal->updateViaApi($request);
    }

    private function noEquipment()
    {
        $this->modalView->newSection()
            ->mrkdwnText("Please select the Equipment you're training for.");
    }

    private function noPerson()
    {
        $this->modalView->newSection()
            ->mrkdwnText("Please select who you're authorizing.");
    }
}
