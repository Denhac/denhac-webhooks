<?php

namespace App\Slack\Modals;


use App\Customer;
use App\Http\Requests\SlackRequest;
use App\TrainableEquipment;
use App\WooCommerce\Api\WooCommerceApi;
use Illuminate\Support\Facades\Log;
use SlackPhp\BlockKit\Kit;
use SlackPhp\BlockKit\Surfaces\Modal;

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
            ->actionId(self::EQUIPMENT_NAME_ACTION_ID)
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
        Log::info("Create Trainable Equipment");
        Log::info(print_r($request->payload(), true));
        $values = $request->payload()['view']['state']['values'];

        $equipmentName = $values[self::EQUIPMENT_NAME_BLOCK_ID][self::EQUIPMENT_NAME_ACTION_ID]['value'];
        $initialTrainerValue = $values[self::INITIAL_TRAINER_BLOCK_ID][self::INITIAL_TRAINER_ACTION_ID]['selected_option']['value'];
        $userSlackChannel = $values[self::USER_SLACK_CHANNEL_BLOCK_ID][self::USER_SLACK_CHANNEL_ACTION_ID]['selected_channel'];
        $userEmail = $values[self::USER_EMAIL_BLOCK_ID][self::USER_EMAIL_ACTION_ID]['value'];
        $trainerSlackChannel = $values[self::TRAINER_SLACK_CHANNEL_BLOCK_ID][self::TRAINER_SLACK_CHANNEL_ACTION_ID]['selected_channel'];
        $trainerEmail = $values[self::TRAINER_EMAIL_BLOCK_ID][self::TRAINER_EMAIL_ACTION_ID]['value'];

        $matches = [];
        $result = preg_match('/customer-(\d+)/', $initialTrainerValue, $matches);
        if (! $result) {
            throw new \Exception("Option wasn't valid for customer: $initialTrainerValue");
        }
        $initialTrainerId = $matches[1];

        /** @var WooCommerceApi $wooCommerceApi */
        $wooCommerceApi = app(WooCommerceApi::class);
        $responseTrainer = $wooCommerceApi->denhac->createUserPlan(
            "$equipmentName Trainer",
            $request->customer()->woo_id
        );
        $trainerPlanId = $responseTrainer['id'];

        $responseUser = $wooCommerceApi->denhac->createUserPlan(
            "$equipmentName User",
            $request->customer()->woo_id
        );
        $userPlanId = $responseUser['id'];

        $trainableEquipmentData = [
            'name' => $equipmentName,
            'user_plan_id' => $userPlanId,
            'trainer_plan_id' => $trainerPlanId
        ];

        if(!empty($userSlackChannel)) $trainableEquipmentData['user_slack_id'] = $userSlackChannel;
        if(!empty($userEmail)) $trainableEquipmentData['user_email'] = $userEmail;
        if(!empty($trainerSlackChannel)) $trainableEquipmentData['trainer_slack_id'] = $trainerSlackChannel;
        if(!empty($trainerEmail)) $trainableEquipmentData['trainer_email'] = $trainerEmail;

        TrainableEquipment::create($trainableEquipmentData);

        $wooCommerceApi->members->addMembership($initialTrainerId, $trainerPlanId);

        return response('');
    }

    public static function getOptions(SlackRequest $request)
    {
        return SelectAMemberModal::getOptions($request);
    }

    public function jsonSerialize()
    {
        return $this->modalView->jsonSerialize();
    }
}
