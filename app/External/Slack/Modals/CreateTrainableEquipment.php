<?php

namespace App\External\Slack\Modals;

use App\External\Slack\BlockActions\RespondsToBlockActions;
use App\Http\Requests\SlackRequest;
use App\Models\Customer;
use App\Models\TrainableEquipment;
use App\Models\UserMembership;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use SlackPhp\BlockKit\Elements\RichTexts\ListStyle;
use SlackPhp\BlockKit\Kit;
use SlackPhp\BlockKit\Parts\TriggerActionsOn;
use SlackPhp\BlockKit\Surfaces\OptionsResult;

class CreateTrainableEquipment implements ModalInterface
{
    use ModalTrait;
    use HasExternalOptions;
    use RespondsToBlockActions;

    private const string EQUIPMENT_NAME = 'equipment-name';

    private const string INITIAL_TRAINER = 'initial-trainer-block';

    private const string USER_SLACK_CHANNEL = 'user-slack-channel';

    private const string USER_EMAIL = 'user-email';

    private const string TRAINER_SLACK_CHANNEL = 'trainer-slack-channel';

    private const string TRAINER_EMAIL = 'trainer-email';

    public function __construct(
        Customer  $submittingUser,
        ?Customer $initialTrainer = null,
        ?string   $equipmentName = null,
    )
    {
        if (is_null($initialTrainer)) {
            $initialTrainer = $submittingUser;
        }

        $name = "$initialTrainer->first_name $initialTrainer->last_name";
        $initialTrainerOption = Kit::option(
            text: $name,
            value: "customer-{$initialTrainer->id}"
        );

        $this->modalView = Kit::modal(
            title: 'New Trainable Equipment',
            callbackId: self::callbackId(),
            clearOnClose: true,
            close: 'Close',
            submit: 'Submit',
            blocks: [
                Kit::input(
                    label: 'Equipment Name',
                    blockId: self::EQUIPMENT_NAME,
                    dispatchAction: true,
                    element: Kit::plainTextInput(
                        actionId: self::EQUIPMENT_NAME,
                        placeholder: 'Name',
                        focusOnLoad: true,
                        // Without the dispatch config, we get "enter" events too, which we don't want.
                        dispatchActionConfig: Kit::dispatchActionConfig([
                            TriggerActionsOn::CHARACTER_ENTERED,
                        ]),
                    ),
                ),
            ],
        );

        if (! is_null($equipmentName)) {
            $equipmentNames = TrainableEquipment::pluck("name");

                $mappedToSimilarity = $equipmentNames->map(fn($name) => [
                    'name' => $name,
                    'value' => similar_text(Str::lower($name), $equipmentName)
                ]);

                $maxValue = $mappedToSimilarity->max('value');
                // Get up to 5 responses with the same comparison score
                $namesToShow = $mappedToSimilarity->where('value', $maxValue)->pluck('name')->take(5);

            if ($namesToShow->count() > 0) {
                $bulletedList = Kit::richTextList(
                    style: ListStyle::BULLET,
                );

                foreach($namesToShow as $name) {
                    $bulletedList->elements(
                        Kit::richTextSection([
                            Kit::text($name),
                        ]),
                    );
                }

                $richTextBlock = Kit::richText([
                    Kit::richTextSection([
                        Kit::text("Please verify that you are not creating a duplicate training item. Similar items:")
                    ]),
                    $bulletedList,
                ]);

                $this->modalView->blocks($richTextBlock);
            }
        }

        $this->modalView->blocks(
            Kit::input(
                label: 'Initial Trainer',
                blockId: self::INITIAL_TRAINER,
                element: Kit::externalSelectMenu(
                    actionId: self::INITIAL_TRAINER,
                    placeholder: 'Select a customer',
                    initialOption: $initialTrainerOption,
                    minQueryLength: 0,
                ),
                dispatchAction: true,
            ),
        );

        // Sometimes people get confused when they mark someone else as the initial trainer and then can't see the
        // equipment they just created. Mostly this seems to stem from the idea that they'll automatically be marked as
        // a trainer. Hopefully this warning message helps.
        if (! is_null($initialTrainer) && $initialTrainer->id != $submittingUser->id) {
            $this->modalView->blocks(
                Kit::context(
                    elements: [
                        Kit::mrkdwnText(
                            text: ':warning: If you are not the initial trainer, you will not see this item in your ' .
                            'list of equipment authorizations.',
                        ),
                    ],
                ),
            );
        }

        $this->modalView->blocks(
            Kit::divider(),
            Kit::header('Slack Channels & Email Groups'),
            Kit::context(
                elements: [
                    Kit::plainText(
                        text: 'Users/trainers will be automatically added to these Slack channels. All are optional'
                    ),
                ],
            ),
            Kit::input(
                label: 'User slack channel',
                blockId: self::USER_SLACK_CHANNEL,
                optional: true,
                element: Kit::channelSelectMenu(
                    actionId: self::USER_SLACK_CHANNEL,
                    placeholder: 'Select a channel',
                ),
            ),
            Kit::input(
                label: 'Trainer slack channel',
                blockId: self::TRAINER_SLACK_CHANNEL,
                optional: true,
                element: Kit::channelSelectMenu(
                    actionId: self::TRAINER_SLACK_CHANNEL,
                    placeholder: 'Select a channel',
                ),
            )
        );
    }

    public static function callbackId(): string
    {
        return 'create-trainable-equipment-modal';
    }

    public static function handle(SlackRequest $request): JsonResponse
    {
        if (! $request->customer()->hasMembership(UserMembership::MEMBERSHIP_META_TRAINER)) {
            Log::warning('CreateTrainableEquipment: Rejecting unauthorized submission from user ' . $request->customer()->id);
            throw new \Exception('Unauthorized');
        }


        $values = $request->payload()['view']['state']['values'];
        $equipmentName = $values[self::EQUIPMENT_NAME][self::EQUIPMENT_NAME]['value'];
        $userSlackChannel = $values[self::USER_SLACK_CHANNEL][self::USER_SLACK_CHANNEL]['selected_channel'];
        $trainerSlackChannel = $values[self::TRAINER_SLACK_CHANNEL][self::TRAINER_SLACK_CHANNEL]['selected_channel'];
        $initialTrainerId = self::getInitialTrainerOption($values);

        $initialTrainer = Customer::find($initialTrainerId);

        app(\App\Actions\WordPress\CreateTrainableEquipment::class)
            ->onQueue()
            ->execute(
                $equipmentName,
                $request->customer(),
                $initialTrainer,
                $userSlackChannel,
                $trainerSlackChannel
            );

        $message = "Equipment submitted for creation. Please do not re-submit this form if the equipment does not show " .
            "up. Instead, ask in #project-webhooks-denhac-org for help.";
        return new SuccessModal($message)->push();
    }

    private static function getInitialTrainerOption(array $values): string
    {
        $initialTrainerValue = $values[self::INITIAL_TRAINER][self::INITIAL_TRAINER]['selected_option']['value'];
        $matches = [];
        $result = preg_match('/customer-(\d+)/', $initialTrainerValue, $matches);
        if (! $result) {
            throw new \Exception("Option wasn't valid for customer: $initialTrainerValue");
        }
        return $matches[1];
    }

    public static function getExternalOptions(SlackRequest $request): OptionsResult
    {
        return SelectAMemberModal::getExternalOptions($request);
    }

    public static function getBlockActions(): array
    {
        return [
            self::blockActionUpdate(self::EQUIPMENT_NAME),
            self::blockActionUpdate(self::INITIAL_TRAINER),
        ];
    }

    public static function onBlockAction(SlackRequest $request)
    {
        $values = $request->payload()['view']['state']['values'];
        $initialTrainerValue = self::getInitialTrainerOption($values);

        $equipmentName = $values[self::EQUIPMENT_NAME][self::EQUIPMENT_NAME]['value'] ?? null;

        $modal = new CreateTrainableEquipment(
            $request->customer(),
            Customer::find($initialTrainerValue),
            $equipmentName
        );

        return $modal->updateViaApi($request);
    }
}
