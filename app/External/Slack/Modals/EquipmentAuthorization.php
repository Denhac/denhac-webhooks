<?php

namespace App\External\Slack\Modals;

use App\Actions\WordPress\BatchAuthorizeEquipment;
use App\Exceptions\UnauthorizedTrainerException;
use App\External\Slack\BlockActions\BlockActionInterface;
use App\External\Slack\BlockActions\RespondsToBlockActions;
use App\Http\Requests\SlackRequest;
use App\Models\Customer;
use App\Models\TrainableEquipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use SlackPhp\BlockKit\Kit;
use SlackPhp\BlockKit\Surfaces\OptionsResult;

class EquipmentAuthorization implements ModalInterface
{
    use ModalTrait;
    use RespondsToBlockActions;
    use HasExternalOptions;

    private const string EQUIPMENT_DROPDOWN = 'equipment-dropdown';

    private const string PERSON_DROPDOWN = 'person-dropdown';

    private const string TRAINER_CHECK = 'trainer-check';

    public function __construct(?Customer $user)
    {
        $equipmentOptions = Kit::optionSet();
        if ($user != null) {
            $trainingList = $user->equipmentTrainer;

            foreach ($trainingList as $equipment) {
                /** @var TrainableEquipment $equipment */
                $equipmentOptions->append(
                    Kit::option(
                        text: $equipment->name,
                        value: "equipment-$equipment->id",
                        // If the user only has one item they can train on, pre-select it.
                        initial: $trainingList->count() == 1
                    )
                );
            }
        }

        $this->modalView = Kit::modal(
            title: 'Equipment Authorization',
            callbackId: self::callbackId(),
            clearOnClose: true,
            close: 'Cancel',
            submit: 'Submit',
            blocks: [
                Kit::input(
                    label: 'Member(s)',
                    blockId: self::PERSON_DROPDOWN,
                    dispatchAction: true,
                    element: Kit::multiExternalSelectMenu(
                        actionId: self::PERSON_DROPDOWN,
                        placeholder: 'Select a member',
                        minQueryLength: 0
                    ),
                ),
                Kit::input(
                    label: 'Equipment',
                    blockId: self::EQUIPMENT_DROPDOWN,
                    dispatchAction: true,
                    element: Kit::multiStaticSelectMenu(
                        actionId: self::EQUIPMENT_DROPDOWN,
                        placeholder: 'Select equipment',
                        options: $equipmentOptions,
                    ),
                ),
                Kit::section()
                    ->text(
                        Kit::mrkdwnText(
                            ':heavy_check_mark: The listed member(s) will be authorized to use this equipment.'
                        )
                    ),
                Kit::actions(
                    blockId: self::TRAINER_CHECK,
                    elements: [
                        Kit::checkboxes(
                            actionId: self::TRAINER_CHECK,
                            options: Kit::optionSet([
                                Kit::option(
                                    text: 'Make Trainer(s)',
                                    value: true,
                                    description: 'Also make these members trainers for this equipment.',
                                ),
                            ]),
                        ),
                    ],
                ),
            ],
        );
    }

    public static function callbackId(): string
    {
        return 'equipment-authorization-modal';
    }

    public static function handle(SlackRequest $request): JsonResponse
    {
        $state = self::getStateValues($request);
        $makeTrainers = ! is_null($state[self::TRAINER_CHECK][self::TRAINER_CHECK] ?? null);

        $selectedEquipment = self::equipmentFromState($state);
        $selectedMembers = self::peopleFromState($state);

        $trainer = $request->customer();

        try {
            app(BatchAuthorizeEquipment::class)->execute($trainer, $selectedMembers, $selectedEquipment, $makeTrainers);
        } catch (UnauthorizedTrainerException) {
            return response()->json([
                'response_action' => 'errors',
                'errors' => [self::EQUIPMENT_DROPDOWN => "You don't have permission to authorize members for this equipment."],
            ]);
        }

        return new SuccessModal("Authorization submitted!")->update();
    }

    /**
     * @return BlockActionInterface[]
     */
    public static function getBlockActions(): array
    {
        return [
            self::blockActionUpdate(self::EQUIPMENT_DROPDOWN),
            self::blockActionUpdate(self::PERSON_DROPDOWN),
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

    public static function getExternalOptions(SlackRequest $request): OptionsResult
    {
        $blockId = $request->payload()['block_id'];

        if ($blockId == self::PERSON_DROPDOWN) {
            return SelectAMemberModal::getExternalOptions($request);
        }

        return Kit::optionsResult();
    }

    public static function onBlockAction(SlackRequest $request)
    {
        // Rerender view to display information about any permissions that the users already have for this equipment.
        $modal = new EquipmentAuthorization($request->customer());

        $state = self::getStateValues($request);

        $selectedEquipment = self::equipmentFromState($state);
        $selectedMembers = self::peopleFromState($state);

        if ($selectedEquipment->isNotEmpty() && $selectedMembers->isNotEmpty()) {

            $alreadyTrained = [];
            $alreadyTrainers = [];

            foreach ($selectedMembers as $person) {
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
            if (! empty($alreadyTrained) || ! empty($alreadyTrainers)) {
                // Render an information section which displays existing permissions
                $modal->modalView->blocks(
                    Kit::section(
                        text: Kit::mrkdwnText(':information_source:'),
                    ),
                );

                foreach ($alreadyTrained as $trainee => $equipmentNames) {
                    $modal->modalView->blocks(
                        Kit::context(
                            elements: [
                                Kit::mrkdwnText($trainee . ' is already trained on ' . $equipmentNames->join(', ')),
                            ],
                        ),
                    );
                }

                foreach ($alreadyTrainers as $trainer => $equipmentNames) {
                    $modal->modalView->blocks(
                        Kit::context(
                            elements: [
                                Kit::mrkdwnText($trainer . ' is already a trainer for ' . $equipmentNames->join(', ')),
                            ],
                        ),
                    );
                }
            }
        }

        return $modal->updateViaApi($request);
    }
}
