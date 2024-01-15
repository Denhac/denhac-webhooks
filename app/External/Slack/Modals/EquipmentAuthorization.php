<?php

namespace App\External\Slack\Modals;

use App\External\Slack\BlockActions\BlockActionInterface;
use App\External\Slack\BlockActions\RespondsToBlockActions;
use App\External\Slack\SlackOptions;
use App\External\WooCommerce\Api\WooCommerceApi;
use App\Http\Requests\SlackRequest;
use App\Models\Customer;
use App\Models\TrainableEquipment;
use Illuminate\Support\Facades\Log;
use SlackPhp\BlockKit\Kit;
use SlackPhp\BlockKit\Partials\Option;
use SlackPhp\BlockKit\Surfaces\Modal;
use Illuminate\Support\Collection;

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
        $this->modalView = Kit::newModal()
            ->callbackId(self::callbackId())
            ->title('Equipment Authorization')
            ->clearOnClose(true)
            ->close('Cancel')
            ->submit('Submit');

        $this->modalView->newInput()
            ->dispatchAction()
            ->blockId(self::PERSON_DROPDOWN)
            ->label('Member(s)')
            ->newMultiSelectMenu()
            ->forExternalOptions()
            ->actionId(self::PERSON_DROPDOWN)
            ->placeholder('Select a member')
            ->minQueryLength(2);

        $this->modalView->newInput()
            ->dispatchAction()
            ->blockId(self::EQUIPMENT_DROPDOWN)
            ->label('Equipment')
            ->newMultiSelectMenu()
            ->forExternalOptions()
            ->actionId(self::EQUIPMENT_DROPDOWN)
            ->placeholder('Select equipment')
            ->minQueryLength(0);

        $this->modalView->newSection()->mrkdwnText(':heavy_check_mark: The listed member(s) will be authorized to use this equipment.');

        $option = Option::new('Make Trainer(s)')
            ->description('Also make these members trainers for this equipment.')
            ->value('true');
        $this->modalView->newActions()
            ->blockId(self::TRAINER_CHECK)
            ->newCheckboxes()
            ->actionId(self::TRAINER_CHECK)
            ->addOption($option, false);
    }

    public static function callbackId(): string
    {
        return 'equipment-authorization-modal';
    }

    public static function handle(SlackRequest $request)
    {
        if (!$request->customer()->isATrainer()) {
            Log::warning('EquipmentAuthorization: Rejecting unauthorized submission from user '.$request->customer()->id);
            throw new \Exception('Unauthorized');
        }

        $state = self::getStateValues($request);
        $makeUsers = true;
        $makeTrainers = ! is_null($state[self::TRAINER_CHECK][self::TRAINER_CHECK] ?? null);

        /** @var WooCommerceApi $api */
        $api = app(WooCommerceApi::class);

        $selectedEquipment = self::equipmentFromState($state);
        $selectedMembers = self::peopleFromState($state);
        
        $actorId = $request->customer()->id;

        foreach($selectedMembers->crossJoin($selectedEquipment)->all() as [$person, $equipment]) {
            if (!$person->hasMembership($equipment->user_plan_id)) {
                Log::info('EquipmentAuthorization: Customer '.$actorId.' authorized Customer '.$person->id.' to use equipment under plan id '.$equipment->user_plan_id);
                $api->members->addMembership($person->id, $equipment->user_plan_id);
            }

            if ($makeTrainers && !$person->hasMembership($equipment->trainer_plan_id)) {
                Log::info('EquipmentAuthorization: Customer '.$actorId.' authorized Customer '.$person->id.' to train on equipment with plan id '.$equipment->trainer_plan_id);
                $api->members->addMembership($person->id, $equipment->trainer_plan_id);
            }
        }


        if (! $makeTrainers && ! $makeUsers) {
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

    public static function equipmentFromState($state): Collection
    {
        return collect($state[self::EQUIPMENT_DROPDOWN][self::EQUIPMENT_DROPDOWN] ?? [])
            -> map(fn($formValue) => str_replace('equipment-', '', $formValue))
            -> map(fn($equipmentId) => TrainableEquipment::find($equipmentId));
    }

    public static function peopleFromState($state): Collection
    {
        return collect($state[self::PERSON_DROPDOWN][self::PERSON_DROPDOWN] ?? [])
            -> map(fn($formValue) => str_replace('customer-', '', $formValue))
            -> map(fn($customerId) => Customer::find($customerId));
    }

    public static function getOptions(SlackRequest $request)
    {
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

        $state = self::getStateValues($request);

        $selectedEquipment = self::equipmentFromState($state);
        $selectedMembers = self::peopleFromState($state);
        
        if ($selectedEquipment->isNotEmpty() && $selectedMembers->isNotEmpty()) {
            // Render information about any selected people who have existing permisisons for this equipment.

            $alreadyTrained = [];
            $alreadyTrainers = [];

            foreach($selectedMembers as $person) {
                $name = "{$person->first_name} {$person->last_name}";
                $trainedEquipment = [];
                $trainerForEquipment = [];

                foreach($selectedEquipment as $equipment) {
                    if ($person->hasMembership($equipment->user_plan_id)) {
                        $trainedEquipment[] = $equipment->name;
                    }
                    if ($person->hasMembership($equipment->trainer_plan_id)) {
                        $trainerForEquipment[] = $equipment->name;
                    }
                }
                if ($trainedEquipment->isNotEmpty()) {
                    $alreadyTrained[$name] = $trainedEquipment;
                }
                if ($trainerForEquipment->isNotEmpty()) {
                    $alreadyTrainers[$name] = $trainerForEquipment;
                }
            }

            if ($alreadyTrained->isNotEmpty() || $alreadyTrainers->isNotEmpty()) {
                $modal->modalView->newSection()->mrkdwnText(":information_source:");

                foreach($alreadyTrained as $trainee => $equipmentNames) {
                    $modal->modalView->newContext()->mrkdwnText($trainee.' is already trained on '.implode(', ', $equipmentNames));
                }

                foreach($alreadyTrainers as $trainer => $equipmentNames) {
                    $modal->modalView->newContext()->mrkdwnText($trainer.' is already a trainer for '.implode(', ', $equipmentNames));
                }
            }
        }

        return $modal->updateViaApi($request);
    }

    private function noEquipment()
    {
        $this->modalView->newSection()
            ->mrkdwnText("Please select at least one piece of equipment above.");
    }

    private function noPerson()
    {
        $this->modalView->newSection()
            ->mrkdwnText("Please select at least one member to authorize.");
    }
}
