<?php

namespace App\Slack\Modals;


use App\Customer;
use App\Http\Requests\SlackRequest;
use Illuminate\Support\Facades\Log;
use Jeremeamia\Slack\BlockKit\Kit;
use Jeremeamia\Slack\BlockKit\Surfaces\Modal;

class CreateTrainableEquipment implements ModalInterface
{
    use ModalTrait;

    private Modal $modalView;

    private const EQUIPMENT_NAME_BLOCK_ID = 'equipment-name-block';
    private const EQUIPMENT_NAME_ACTION_ID = 'equipment-name-action';
    private const INITIAL_TRAINER_BLOCK_ID = 'initial-trainer-block';
    private const INITIAL_TRAINER_ACTION_ID = 'initial-trainer-action';
    private const USER_SLACK_CHANNEL_BLOCK_ID = 'user-slack-channel-block';
    private const USER_SLACK_CHANNEL_ACTION_ID = 'user-slack-channel-action';
    private const USER_EMAIL_BLOCK_ID = 'user-email-block';
    private const USER_EMAIL_ACTION_ID = 'user-email-action';
    private const TRAINER_SLACK_CHANNEL_BLOCK_ID = 'trainer-slack-channel-block';
    private const TRAINER_SLACK_CHANNEL_ACTION_ID = 'trainer-slack-channel-action';
    private const TRAINER_EMAIL_BLOCK_ID = 'trainer-email-block';
    private const TRAINER_EMAIL_ACTION_ID = 'trainer-email-action';

    public function __construct(?Customer $user)
    {
        $this->modalView = Kit::newModal()
            ->callbackId(self::callbackId())
            ->title('New Trainable Equipment')
            ->clearOnClose(true)
            ->close('Cancel')
            ->submit('Submit');

        $this->modalView->newInput()
            ->label("Equipment Name")
            ->blockId(self::EQUIPMENT_NAME_BLOCK_ID)
            ->newTextInput()
            ->actionId(self::EQUIPMENT_NAME_BLOCK_ID)
            ->placeholder("Name");

        $trainerInput = $this->modalView->newInput()
            ->label('Initial Trainer')
            ->blockId(self::INITIAL_TRAINER_BLOCK_ID)
            ->newSelectMenu()
            ->forExternalOptions()
            ->actionId(self::INITIAL_TRAINER_ACTION_ID)
            ->placeholder("Select a customer")
            ->minQueryLength(0);

        if (!is_null($user)) {
            $name = "{$user->first_name} {$user->last_name}";
            $trainerInput->initialOption($name, "customer-{$user->woo_id}");
        }

        $this->modalView->divider();

        $this->modalView->header("Slack Channels & Email Groups");

        $this->modalView->newContext()
            ->mrkdwnText(
                "Users/trainers will be automatically added to these channels/emails. All are optional. " .
                "Email must be an existing group, for now. Please ask in #general and we'll help make one " .
                "if needed."
            );

        $this->modalView->newInput()
            ->label('User slack channel')
            ->blockId(self::USER_SLACK_CHANNEL_BLOCK_ID)
            ->optional(true)
            ->newSelectMenu()
            ->forChannels()
            ->placeholder("Select a channel")
            ->actionId(self::USER_SLACK_CHANNEL_ACTION_ID);

        $this->modalView->newInput()
            ->label('User email')
            ->blockId(self::USER_EMAIL_BLOCK_ID)
            ->optional(true)
            ->newTextInput()
            ->actionId(self::USER_EMAIL_ACTION_ID);

        $this->modalView->newInput()
            ->label('Trainer slack channel')
            ->blockId(self::TRAINER_SLACK_CHANNEL_BLOCK_ID)
            ->optional(true)
            ->newSelectMenu()
            ->forChannels()
            ->placeholder("Select a channel")
            ->actionId(self::TRAINER_SLACK_CHANNEL_ACTION_ID);

        $this->modalView->newInput()
            ->label('Trainer email')
            ->blockId(self::TRAINER_EMAIL_BLOCK_ID)
            ->optional(true)
            ->newTextInput()
            ->actionId(self::TRAINER_EMAIL_ACTION_ID);
    }

    public static function callbackId(): string
    {
        return 'create-trainable-equipment-modal';
    }

    public static function handle(SlackRequest $request)
    {
        Log::info("Create trainable equipment!");
        Log::info(print_r($request->payload(), true));

        return response('');
    }

    public static function getOptions(SlackRequest $request)
    {
        return SelectAMemberModal::getOptions($request);
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return $this->modalView->jsonSerialize();
    }
}
