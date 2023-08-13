<?php

namespace App\External\Slack\Modals;


use App\Actions\CountdownModalLoop;
use App\External\Slack\BlockActions\RespondsToBlockActions;
use App\Http\Requests\SlackRequest;
use Illuminate\Support\Facades\Log;
use SlackPhp\BlockKit\Kit;
use SlackPhp\BlockKit\Surfaces\Modal;

class CountdownTestModal implements ModalInterface
{
    use ModalTrait;
    use RespondsToBlockActions;

    private const START_COUNTDOWN = 'start-countdown';

    private Modal $modalView;

    public function __construct(int|null $timeLeft)
    {
        $this->modalView = Kit::newModal()
            ->callbackId(self::callbackId())
            ->title("Countdown Test")
            ->clearOnClose(true)
            ->clearOnClose('Close');

        $this->modalView->newSection()
            ->mrkdwnText("This is a test to see how responsive something like a countdown is.");

        if (is_null($timeLeft)) {
            $this->modalView->newSection()
                ->mrkdwnText("The countdown has not yet started. Press the button to start.");

            $this->modalView->newActions(self::START_COUNTDOWN)
                ->newButton(self::START_COUNTDOWN)
                ->asPrimary()
                ->text("Start Countdown");
        } else if ($timeLeft == -1) {
            $this->modalView->newSection()
                ->mrkdwnText("Countdown hopefully started!");
        } else if ($timeLeft > 0) {
            $this->modalView->newSection()
                ->mrkdwnText("The countdown has {$timeLeft} seconds left.");
        } else {
            $this->modalView->newSection()
                ->mrkdwnText("The countdown is over! Thanks for testing!");
        }
    }

    public static function callbackId(): string
    {
        return 'countdown-test-modal';
    }

    public static function handle(SlackRequest $request)
    {
        return self::clearViewStack();
    }

    public static function getOptions(SlackRequest $request)
    {
    }

    public function jsonSerialize()
    {
        return $this->modalView->jsonSerialize();
    }

    public static function getBlockActions(): array
    {
        return [
            self::blockActionUpdate(self::START_COUNTDOWN),
        ];
    }

    static function onBlockAction(SlackRequest $request)
    {
        Log::info(print_r($request->payload(), true));
        $viewId = $request->payload()['view']['id'];
        app(CountdownModalLoop::class)
            ->onQueue()
            ->execute($viewId);
        return new CountdownTestModal(-1);
    }
}
