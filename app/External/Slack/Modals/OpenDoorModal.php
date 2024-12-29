<?php

namespace App\External\Slack\Modals;

use App\Events\DoorControlUpdated;
use App\External\Slack\SlackApi;
use App\External\WinDSX\Door;
use App\Http\Requests\SlackRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use SlackPhp\BlockKit\Collections\OptionSet;
use SlackPhp\BlockKit\Kit;
use SlackPhp\BlockKit\Surfaces\Modal;

class OpenDoorModal implements ModalInterface
{
    use ModalTrait;

    private const DOORS = 'doors';

    private Modal $modalView;

    public function __construct()
    {
        $doorButtonOptions = Kit::optionSet();

        /** @var Door $door */
        foreach (Door::all() as $door) {
            $doorButtonOptions->append(Kit::option(
                text: $door->humanReadableName,
                value: 'device-' . $door->dsxDeviceId,
            ));
        }

        $this->modalView = Kit::modal(
            title: 'Open door',
            callbackId: self::callbackId(),
            clearOnClose: true,
            close: 'Cancel',
            submit: 'Open',
            blocks: [
                Kit::input(
                    label: 'Doors',
                    blockId: self::DOORS,
                    element: Kit::radioButtons(
                        actionId: self::DOORS,
                        options: $doorButtonOptions,
                    )
                ),
            ],
        );
    }

    public static function callbackId(): string
    {
        return 'door-open-modal';
    }

    public static function handle(SlackRequest $request): JsonResponse
    {
        $customer = $request->customer();
        $values = $request->payload()['view']['state']['values'];
        Log::info('Open Door Modal: ' . print_r($values, true));

        $selectedOption = collect($values[self::DOORS][self::DOORS]['selected_option']);

        /** @var Door $door */
        $door = Door::all()
            ->filter(fn($door) => $selectedOption['value'] == 'device-' . $door->dsxDeviceId)
            ->first();

        if (is_null($door)) {
            throw new \Exception("The door for {$selectedOption['value']} was null");
        }

        /** @var SlackApi $slackApi */
        $slackApi = app(SlackApi::class);
        $accessLogs = collect($slackApi->team->accessLogs());

        $earliestAllowedTimestamp = Carbon::now()->subMinutes(5)->getTimestamp();
        $atTheSpace = $accessLogs
                ->where('user_id', $customer->slack_id)
                ->where('ip', setting('ip.space'))
                ->filter(fn($data) => $data['date_last'] >= $earliestAllowedTimestamp)
                ->count() > 0;

        if ($atTheSpace) {
            event(new DoorControlUpdated($door->momentaryOpenTime, $door->shouldOpen(true)));
        } else {
            return response()->json([
                'response_action' => 'push',
                'view' => new FailureModal("I'm sorry, I can't verify that you're at the space"),
            ]);
        }

        return self::clearViewStack();
    }

    public static function getOptions(SlackRequest $request): OptionSet
    {
        return Kit::optionSet();
    }

    public function jsonSerialize(): array
    {
        $this->modalView->validate();

        return $this->modalView->jsonSerialize();
    }
}
