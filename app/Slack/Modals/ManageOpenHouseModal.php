<?php

namespace App\Slack\Modals;

use App\Events\DoorControlUpdated;
use App\Http\Requests\SlackRequest;
use App\WinDSX\Door;
use Illuminate\Support\Facades\Log;
use Jeremeamia\Slack\BlockKit\Inputs\TimePicker;
use Jeremeamia\Slack\BlockKit\Kit;
use Jeremeamia\Slack\BlockKit\Partials\Option;
use Jeremeamia\Slack\BlockKit\Surfaces\Modal;

class ManageOpenHouseModal implements ModalInterface
{
    use ModalTrait;

    private const EXPIRES_TIME_BLOCK_ID = 'expires-time-block';
    private const EXPIRES_TIME_ACTION_ID = 'expires-time-action';
    private const DOORS_BLOCK_ID = 'doors-block-id';
    private const DOORS_ACTION_ID = 'doors-action-id';
    private const CLOSE_ALL_DOORS = 'close-all-doors';

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
            ->title('Manage Open House Doors')
            ->clearOnClose(true)
            ->submit("Update")
            ->close('Cancel');

        $timePicker = (new TimePicker())
            ->initialTime("23:00")
            ->actionId(self::EXPIRES_TIME_ACTION_ID);

        $this->modalView->newSection()
            ->mrkdwnText("When should these doors close?")
            ->blockId(self::EXPIRES_TIME_BLOCK_ID)
            ->setAccessory($timePicker);

        $checkboxes = $this->modalView->newInput()
            ->label("Doors")
            ->blockId(self::DOORS_BLOCK_ID)
            ->newCheckboxes(self::DOORS_ACTION_ID);

        /** @var Door $door */
        foreach (Door::all() as $door) {
            $option = Option::new($door->humanReadableName, "device-".$door->dsxDeviceId);
            $checkboxes->addOption($option, $door->openDuringOpenHouseByDefault);
        }

        $checkboxes->option("Close all doors", self::CLOSE_ALL_DOORS);
    }

    public static function callbackId()
    {
        return 'manage-open-house-modal';
    }

    public static function handle(SlackRequest $request)
    {
        $values = $request->payload()['view']['state']['values'];
        Log::info("Manage open house modal: " . print_r($values, true));

        $selectedTime = $values[self::EXPIRES_TIME_BLOCK_ID][self::EXPIRES_TIME_ACTION_ID]['selected_time'];
        $selectedOptions = collect($values[self::DOORS_BLOCK_ID][self::DOORS_ACTION_ID]['selected_options'])
            ->map(function($option) {
                return $option['value'];
            });

        $selectedTimeCarbon = now()->tz("America/Denver");
        $selectedTime = explode(":", $selectedTime);
        $selectedTimeCarbon->hour = $selectedTime[0];
        $selectedTimeCarbon->minute = $selectedTime[1];

        $doors = Door::all();
        /** @var Door $door */
        foreach ($doors as $door) {
            $shouldOpen = $selectedOptions->contains("device-".$door->dsxDeviceId);
            if($selectedOptions->contains(self::CLOSE_ALL_DOORS)) {
                $door->shouldOpen(false);
            } else {
                $door->shouldOpen($shouldOpen);
            }
        }

        event(new DoorControlUpdated($selectedTimeCarbon, ...$doors->toArray()));

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
