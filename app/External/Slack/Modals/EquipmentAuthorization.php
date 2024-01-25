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
use App\Actions\WordPress\BatchAuthorizeEquipment;

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
        $makeTrainers = ! is_null($state[self::TRAINER_CHECK][self::TRAINER_CHECK] ?? null);

        /** @var WooCommerceApi $api */
        $api = app(WooCommerceApi::class);

        $selectedEquipment = self::equipmentFromState($state);
        $selectedMembers = self::peopleFromState($state);
        
        $actor = $request->customer();

        try {
            app()->make(BatchAuthorizeEquipment::class)->execute($actor, $selectedMembers, $selectedEquipment, $makeTrainers);
        } catch (\Exception $e) {
            if ($e->getMessage() == 'NotAuthorized') {
                return response()->json([
                    'response_action' => 'errors',
                    'errors' => [self::EQUIPMENT_DROPDOWN => "You don't have permission to authorize members for this equipment."]
                ]);
            }
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
        $equipmentIds = array_map(
            fn($formValue) => str_replace('equipment-', '', $formValue), 
            $state[self::EQUIPMENT_DROPDOWN][self::EQUIPMENT_DROPDOWN] ?? []
        );
        return TrainableEquipment::whereIn('id', $equipmentIds)->get();
    }

    public static function peopleFromState($state): Collection
    {
        $customerIds = array_map(
            fn($formValue) => str_replace('customer-', '', $formValue),
            $state[self::PERSON_DROPDOWN][self::PERSON_DROPDOWN] ?? []
        );
        return Customer::with('memberships')->whereIn('id', $customerIds)->get();
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
        // Rerender view to display information about any permissions that the users already have for this equipment.
        $modal = new EquipmentAuthorization();

        $state = self::getStateValues($request);

        $selectedEquipment = self::equipmentFromState($state);
        $selectedMembers = self::peopleFromState($state);
        
        if ($selectedEquipment->isNotEmpty() && $selectedMembers->isNotEmpty()) {

            $alreadyTrained = [];
            $alreadyTrainers = [];

            foreach($selectedMembers as $person) {
                $traineeName = "{$person->first_name} {$person->last_name}";
                
                // Get names of equipment for which the member is already a user
                $trainedEquipmentNames = $selectedEquipment
                    ->where(fn($e) => $person->hasMembership($e->user_plan_id))
                    ->map(fn($e) => $e->name);
                if ($trainedEquipmentNames->isNotEmpty()) {
                    $alreadyTrained[$traineeName] = $trainedEquipmentNames;
                }

                // Get names of equipment for which the member is already a trainer
                $trainerForEquipmentNames = $selectedEquipment
                    ->where(fn($e) => $person->hasMembership($e->trainer_plan_id))
                    ->map(fn($e) => $e->name);
                if ($trainerForEquipmentNames->isNotEmpty()) {
                    $alreadyTrainers[$traineeName] = $trainerForEquipmentNames;
                }
            }

            // NOTE: $alreadyTrained and $alreadyTrainers are arrays, where as most other iterables in this function are Collections.
            // Use `empty` on arrays, and `Collection->isEmpty` on Collections.
            if (!empty($alreadyTrained) || !empty($alreadyTrainers)) {
                // Render an information section which displays existing permissions
                $modal->modalView->newSection()->mrkdwnText(":information_source:");

                foreach($alreadyTrained as $trainee => $equipmentNames) {
                    $modal->modalView->newContext()->mrkdwnText($trainee.' is already trained on '.$equipmentNames->join(', '));
                }

                foreach($alreadyTrainers as $trainer => $equipmentNames) {
                    $modal->modalView->newContext()->mrkdwnText($trainer.' is already a trainer for '.$equipmentNames->join(', '));
                }
            }
        }

        return $modal->updateViaApi($request);
    }
}
