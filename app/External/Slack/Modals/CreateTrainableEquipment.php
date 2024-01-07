<?php

namespace App\External\Slack\Modals;

use App\External\WooCommerce\Api\WooCommerceApi;
use App\Http\Requests\SlackRequest;
use App\Models\Customer;
use App\Models\TrainableEquipment;
use Illuminate\Support\Facades\Log;
use SlackPhp\BlockKit\Kit;
use SlackPhp\BlockKit\Surfaces\Modal;

class CreateTrainableEquipment implements ModalInterface
{
    use ModalTrait;

    private Modal $modalView;

    private const EQUIPMENT_NAME = 'equipment-name';

    private const INITIAL_TRAINER = 'initial-trainer-block';

    private const USER_SLACK_CHANNEL = 'user-slack-channel';

    private const USER_EMAIL = 'user-email';

    private const TRAINER_SLACK_CHANNEL = 'trainer-slack-channel';

    private const TRAINER_EMAIL = 'trainer-email';

    public function __construct(?Customer $user)
    {
        $this->modalView = Kit::newModal()
            ->callbackId(self::callbackId())
            ->title('New Trainable Equipment')
            ->clearOnClose(true)
            ->close('Cancel')
            ->submit('Submit');

        $this->modalView->newInput()
            ->label('Equipment Name')
            ->blockId(self::EQUIPMENT_NAME)
            ->newTextInput()
            ->actionId(self::EQUIPMENT_NAME)
            ->placeholder('Name');

        $trainerInput = $this->modalView->newInput()
            ->label('Initial Trainer')
            ->blockId(self::INITIAL_TRAINER)
            ->newSelectMenu()
            ->forExternalOptions()
            ->actionId(self::INITIAL_TRAINER)
            ->placeholder('Select a customer')
            ->minQueryLength(0);

        if (! is_null($user)) {
            $name = "{$user->first_name} {$user->last_name}";
            $trainerInput->initialOption($name, "customer-{$user->id}");
        }

        $this->modalView->divider();

        $this->modalView->header('Slack Channels & Email Groups');

        $this->modalView->newContext()
            ->mrkdwnText(
                'Users/trainers will be automatically added to these channels/emails. All are optional. '.
                "Email must be an existing group, for now. Please ask in #general and we'll help make one ".
                'if needed.'
            );

        $this->modalView->newInput()
            ->label('User slack channel')
            ->blockId(self::USER_SLACK_CHANNEL)
            ->optional(true)
            ->newSelectMenu()
            ->forChannels()
            ->placeholder('Select a channel')
            ->actionId(self::USER_SLACK_CHANNEL);

        $this->modalView->newInput()
            ->label('User email')
            ->blockId(self::USER_EMAIL)
            ->optional(true)
            ->newTextInput()
            ->actionId(self::USER_EMAIL);

        $this->modalView->newInput()
            ->label('Trainer slack channel')
            ->blockId(self::TRAINER_SLACK_CHANNEL)
            ->optional(true)
            ->newSelectMenu()
            ->forChannels()
            ->placeholder('Select a channel')
            ->actionId(self::TRAINER_SLACK_CHANNEL);

        $this->modalView->newInput()
            ->label('Trainer email')
            ->blockId(self::TRAINER_EMAIL)
            ->optional(true)
            ->newTextInput()
            ->actionId(self::TRAINER_EMAIL);
    }

    public static function callbackId(): string
    {
        return 'create-trainable-equipment-modal';
    }

    public static function handle(SlackRequest $request)
    {
        $values = $request->payload()['view']['state']['values'];

        $equipmentName = $values[self::EQUIPMENT_NAME][self::EQUIPMENT_NAME]['value'];
        $initialTrainerValue = $values[self::INITIAL_TRAINER][self::INITIAL_TRAINER]['selected_option']['value'];
        $userSlackChannel = $values[self::USER_SLACK_CHANNEL][self::USER_SLACK_CHANNEL]['selected_channel'];
        $userEmail = $values[self::USER_EMAIL][self::USER_EMAIL]['value'];
        $trainerSlackChannel = $values[self::TRAINER_SLACK_CHANNEL][self::TRAINER_SLACK_CHANNEL]['selected_channel'];
        $trainerEmail = $values[self::TRAINER_EMAIL][self::TRAINER_EMAIL]['value'];

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
            $request->customer()->id
        );
        $trainerPlanId = $responseTrainer['id'];

        $responseUser = $wooCommerceApi->denhac->createUserPlan(
            "$equipmentName User",
            $request->customer()->id
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
        if (! empty($userEmail)) {
            $trainableEquipmentData['user_email'] = $userEmail;
        }
        if (! empty($trainerSlackChannel)) {
            $trainableEquipmentData['trainer_slack_id'] = $trainerSlackChannel;
        }
        if (! empty($trainerEmail)) {
            $trainableEquipmentData['trainer_email'] = $trainerEmail;
        }

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
