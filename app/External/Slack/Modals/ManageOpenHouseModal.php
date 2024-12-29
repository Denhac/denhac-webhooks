<?php

namespace App\External\Slack\Modals;

use App\Events\DoorControlUpdated;
use App\External\Slack\BlockActions\BlockActionInterface;
use App\External\Slack\BlockActions\RespondsToBlockActions;
use App\External\WinDSX\Door;
use App\Http\Requests\SlackRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use SlackPhp\BlockKit\Collections\OptionSet;
use SlackPhp\BlockKit\Kit;
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
        $doorCheckboxOptions = Kit::optionSet();

        /** @var Door $door */
        foreach (Door::all() as $door) {
            $doorCheckboxOptions->append(Kit::option(
                text: $door->humanReadableName,
                value: 'device-' . $door->dsxDeviceId,
                initial: $door->openDuringOpenHouseByDefault
            ));
        }
        $doorCheckboxOptions->append(Kit::option(
            text: 'Close all doors',
            value: self::CLOSE_ALL_DOORS,
        ));

        $this->modalView = Kit::modal(
            title: 'Manage Open House Doors',
            callbackId: self::callbackId(),
            clearOnClose: true,
            submit: 'Update',
            close: 'Cancel',
            blocks: [
                Kit::section(
                    text: Kit::plainText('When should these doors close?'),
                    blockId: self::EXPIRES_TIME,
                    accessory: Kit::timePicker(
                        initialTime: '22:00',
                        actionId: self::EXPIRES_TIME
                    ),
                ),
                Kit::input(
                    label: 'Doors',
                    blockId: self::DOORS,
                    element: Kit::checkboxes(
                        actionId: self::DOORS,
                        options: $doorCheckboxOptions,
                    ),
                ),
            ],
        );
    }

    public static function callbackId(): string
    {
        return 'manage-open-house-modal';
    }

    public static function handle(SlackRequest $request): JsonResponse
    {
        if (! $request->customer()->canIDcheck()) {
            Log::warning('ManageOpenHouseModal: Rejecting unauthorized submission from user ' . $request->customer()->id);
            throw new \Exception('Unauthorized');
        }

        $values = $request->payload()['view']['state']['values'];
        Log::info('Manage open house modal: ' . print_r($values, true));

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
            $shouldOpen = $selectedOptions->contains('device-' . $door->dsxDeviceId);
            if ($selectedOptions->contains(self::CLOSE_ALL_DOORS)) {
                $door->shouldOpen(false);
            } else {
                $door->shouldOpen($shouldOpen);
            }
        }

        event(new DoorControlUpdated($selectedTimeCarbon, ...$doors->toArray()));

        return self::clearViewStack();
    }

    public static function getOptions(SlackRequest $request): OptionSet
    {
        return Kit::optionSet();
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

    public function jsonSerialize(): array
    {
        $this->modalView->validate();

        return $this->modalView->jsonSerialize();
    }

    public static function onBlockAction(SlackRequest $request)
    {
        // TODO Maybe handle if time is before now so it rolls over?
        return response('');
    }
}
