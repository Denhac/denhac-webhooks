<?php

namespace App\External\Slack\Modals;

use App\Events\DoorControlUpdated;
use App\External\Slack\BlockActions\BlockActionInterface;
use App\External\Slack\BlockActions\RespondsToBlockActions;
use App\External\WinDSX\Door;
use App\Http\Requests\SlackRequest;
use Illuminate\Support\Facades\Log;
use SlackPhp\BlockKit\Inputs\TimePicker;
use SlackPhp\BlockKit\Kit;
use SlackPhp\BlockKit\Partials\Option;
use SlackPhp\BlockKit\Surfaces\Modal;

class ManageOpenHouseModal implements ModalInterface
{
    use ModalTrait;
    use RespondsToBlockActions;

    protected const EXPIRES_TIME = 'expires-time';

    private const DOORS = 'doors';

    private const CLOSE_ALL_DOORS = 'close-all-doors';

    private Modal $modalView;

    public function __construct()
    {
        $this->modalView = Kit::newModal()
            ->callbackId(self::callbackId())
            ->title('Manage Open House Doors')
            ->clearOnClose(true)
            ->submit('Update')
            ->close('Cancel');

        $timePicker = (new TimePicker())
            ->initialTime('22:00')
            ->actionId(self::EXPIRES_TIME);

        $this->modalView->newSection()
            ->mrkdwnText('When should these doors close?')
            ->blockId(self::EXPIRES_TIME)
            ->setAccessory($timePicker);

        $checkboxes = $this->modalView->newInput()
            ->label('Doors')
            ->blockId(self::DOORS)
            ->newCheckboxes(self::DOORS);

        /** @var Door $door */
        foreach (Door::all() as $door) {
            $option = Option::new($door->humanReadableName, 'device-'.$door->dsxDeviceId);
            $checkboxes->addOption($option, $door->openDuringOpenHouseByDefault);
        }

        $checkboxes->option('Close all doors', self::CLOSE_ALL_DOORS);
    }

    public static function callbackId(): string
    {
        return 'manage-open-house-modal';
    }

    public static function handle(SlackRequest $request)
    {
        if (!$request->customer()->canIDcheck()) {
            Log::warning('ManageOpenHouseModal: Rejecting unauthorized submission from user '.$request->customer()->id);
            throw new \Exception('Unauthorized');
        }

        $values = $request->payload()['view']['state']['values'];
        Log::info('Manage open house modal: '.print_r($values, true));

        $selectedTime = $values[self::EXPIRES_TIME][self::EXPIRES_TIME]['selected_time'];
        $selectedOptions = collect($values[self::DOORS][self::DOORS]['selected_options'])
            ->map(function ($option) {
                return $option['value'];
            });

        $selectedTimeCarbon = now()->tz('America/Denver');
        $selectedTime = explode(':', $selectedTime);
        $selectedTimeCarbon->hour = $selectedTime[0];
        $selectedTimeCarbon->minute = $selectedTime[1];

        $doors = Door::all();
        /** @var Door $door */
        foreach ($doors as $door) {
            $shouldOpen = $selectedOptions->contains('device-'.$door->dsxDeviceId);
            if ($selectedOptions->contains(self::CLOSE_ALL_DOORS)) {
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
     * @return BlockActionInterface[]
     */
    public static function getBlockActions(): array
    {
        return [
            self::blockActionUpdate(self::EXPIRES_TIME),
        ];
    }

    public function jsonSerialize()
    {
        return $this->modalView->jsonSerialize();
    }

    public static function onBlockAction(SlackRequest $request)
    {
        // TODO Maybe handle if time is before now so it rolls over?
        return response('');
    }
}
