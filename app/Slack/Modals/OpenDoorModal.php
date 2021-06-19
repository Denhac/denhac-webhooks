<?php

namespace App\Slack\Modals;

use App\Events\DoorControlUpdated;
use App\Http\Requests\SlackRequest;
use App\WinDSX\Door;
use Illuminate\Support\Facades\Log;
use Jeremeamia\Slack\BlockKit\Kit;
use Jeremeamia\Slack\BlockKit\Partials\Option;
use Jeremeamia\Slack\BlockKit\Surfaces\Modal;

class OpenDoorModal implements ModalInterface
{
    use ModalTrait;

    private const DOORS_BLOCK_ID = 'doors-block-id';
    private const DOORS_ACTION_ID = 'doors-action-id';

    /**
     * @var Modal
     */
    private Modal $modalView;

    /**
     * ManageMembersCardsModal constructor.
     */
    public function __construct()
    {
        $this->modalView = Kit::newModal()
            ->callbackId(self::callbackId())
            ->title('Open door')
            ->clearOnClose(true)
            ->submit("Open")
            ->close('Cancel');

        $buttons = $this->modalView->newInput()
            ->label("Doors")
            ->blockId(self::DOORS_BLOCK_ID)
            ->newRadioButtons(self::DOORS_ACTION_ID);

        /** @var Door $door */
        foreach (Door::all() as $door) {
            $option = Option::new($door->humanReadableName, "device-".$door->dsxDeviceId);
            $buttons->addOption($option);
        }
    }

    public static function callbackId(): string
    {
        return 'door-open-modal';
    }

    public static function handle(SlackRequest $request)
    {
        $customer = $request->customer();
        $values = $request->payload()['view']['state']['values'];
        Log::info("Open Door Modal: " . print_r($values, true));

        $selectedOption = collect($values[self::DOORS_BLOCK_ID][self::DOORS_ACTION_ID]['selected_option']);

        $door = Door::all()
            ->filter(fn($door) => $selectedOption == "device-".$door->dsxDeviceId)
            ->first();

        if(is_null($door)) {
            throw new \Exception("The door for $selectedOption was null");
        }

        if ($customer->hasCapability('denhac_can_verify_member_id')) {
            event(new DoorControlUpdated(5, $door));
        }

        return self::clearViewStack();
    }

    public static function getOptions(SlackRequest $request)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->modalView->jsonSerialize();
    }
}
