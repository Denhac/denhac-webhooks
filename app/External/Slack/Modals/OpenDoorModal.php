<?php

namespace App\External\Slack\Modals;

use App\Events\DoorControlUpdated;
use App\External\Slack\SlackApi;
use App\External\WinDSX\Door;
use App\Http\Requests\SlackRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use SlackPhp\BlockKit\Kit;
use SlackPhp\BlockKit\Partials\Option;
use SlackPhp\BlockKit\Surfaces\Modal;

class OpenDoorModal implements ModalInterface
{
    use ModalTrait;

    private const DOORS_BLOCK_ID = 'doors-block-id';

    private const DOORS_ACTION_ID = 'doors-action-id';

    private Modal $modalView;

    public function __construct()
    {
        $this->modalView = Kit::newModal()
            ->callbackId(self::callbackId())
            ->title('Open door')
            ->clearOnClose(true)
            ->submit('Open')
            ->close('Cancel');

        $buttons = $this->modalView->newInput()
            ->label('Doors')
            ->blockId(self::DOORS_BLOCK_ID)
            ->newRadioButtons(self::DOORS_ACTION_ID);

        /** @var Door $door */
        foreach (Door::all() as $door) {
            $option = Option::new($door->humanReadableName, 'device-'.$door->dsxDeviceId);
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
        Log::info('Open Door Modal: '.print_r($values, true));

        $selectedOption = collect($values[self::DOORS_BLOCK_ID][self::DOORS_ACTION_ID]['selected_option']);

        /** @var Door $door */
        $door = Door::all()
            ->filter(fn ($door) => $selectedOption['value'] == 'device-'.$door->dsxDeviceId)
            ->first();

        if (is_null($door)) {
            throw new \Exception("The door for ${selectedOption['value']} was null");
        }

        /** @var SlackApi $slackApi */
        $slackApi = app(SlackApi::class);
        $accessLogs = collect($slackApi->team->accessLogs());

        $earliestAllowedTimestamp = Carbon::now()->subMinutes(5)->getTimestamp();
        $atTheSpace = $accessLogs
            ->where('user_id', $customer->slack_id)
            ->where('ip', setting('ip.space'))
            ->filter(fn ($data) => $data['date_last'] >= $earliestAllowedTimestamp)
            ->count() > 0;

        if ($atTheSpace) {
            event(new DoorControlUpdated($door->momentaryOpenTime, $door->shouldOpen(true)));
        } else {
            return response()->json([
                'response_action' => 'push',
                'view' => Kit::newModal()
                    ->title('Failed')
                    ->clearOnClose(true)
                    ->close('Close')
                    ->text("I'm sorry, I can't verify that you're at the space"),
            ]);
        }

        return self::clearViewStack();
    }

    public static function getOptions(SlackRequest $request)
    {
        return [];
    }

    public function jsonSerialize()
    {
        return $this->modalView->jsonSerialize();
    }
}
