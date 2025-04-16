<?php

namespace App\External\Slack\Modals;

use App\Actions\CountdownModalLoop;
use App\External\Slack\BlockActions\RespondsToBlockActions;
use App\Http\Requests\SlackRequest;
use Illuminate\Http\JsonResponse;
use SlackPhp\BlockKit\Elements\ButtonStyle;
use SlackPhp\BlockKit\Kit;

class CountdownTestModal implements ModalInterface
{
    use ModalTrait;
    use RespondsToBlockActions;

    private const START_COUNTDOWN = 'start-countdown';

    public function __construct(?int $timeLeft)
    {
        $this->modalView = Kit::modal(
            title: 'Countdown Test',
            callbackId: self::callbackId(),
            close: 'Close',
            clearOnClose: true,
            blocks: [
                Kit::section(
                    text: 'This is a test to see how responsive something like a countdown is.'
                ),
            ],
        );

        if (is_null($timeLeft)) {
            $this->modalView->blocks(
                Kit::section(
                    text: 'The countdown has not yet started. Press the button to start.'
                ),
                Kit::actions(
                    elements: [
                        Kit::button(
                            actionId: self::START_COUNTDOWN,
                            text: 'Start Countdown',
                            style: ButtonStyle::PRIMARY,
                        ),
                    ],
                    blockId: self::START_COUNTDOWN
                ),
            );
        } elseif ($timeLeft == -1) {
            $this->modalView->blocks(
                Kit::section(
                    text: 'Countdown hopefully started!'
                ),
            );
        } elseif ($timeLeft > 0) {
            $this->modalView->blocks(
                Kit::section(
                    text: "The countdown has {$timeLeft} seconds left."
                ),
            );
        } else {
            $this->modalView->blocks(
                Kit::section(
                    text: 'The countdown is over! Thanks for testing!'
                ),
            );
        }
    }

    public static function callbackId(): string
    {
        return 'countdown-test-modal';
    }

    public static function handle(SlackRequest $request): JsonResponse
    {
        return self::clearViewStack();
    }

    public static function getBlockActions(): array
    {
        return [
            self::blockActionUpdate(self::START_COUNTDOWN),
        ];
    }

    public static function onBlockAction(SlackRequest $request)
    {
        $viewId = $request->payload()['view']['id'];
        app(CountdownModalLoop::class)
            ->onQueue()
            ->execute($viewId);

        $modal = new CountdownTestModal(-1);
        return $modal->updateViaApi($request);
    }
}
