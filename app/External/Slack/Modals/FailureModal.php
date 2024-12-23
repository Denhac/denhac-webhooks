<?php

namespace App\External\Slack\Modals;

use App\Http\Requests\SlackRequest;
use SlackPhp\BlockKit\Kit;
use SlackPhp\BlockKit\Surfaces\Modal;

class FailureModal implements ModalInterface
{
    use ModalTrait;

    private Modal $modalView;

    public function __construct($message)
    {
        $this->modalView = Kit::modal(
            title: 'Failure! :(',
            callbackId: self::callbackId(),
            clearOnClose: true,
            close: 'Close',
            blocks: [
                Kit::section(
                    text: $message,
                ),
            ],
        );
    }

    public static function callbackId(): string
    {
        return 'failure-modal';
    }

    public static function handle(SlackRequest $request)
    {
        return self::clearViewStack();
    }

    public static function getOptions(SlackRequest $request)
    {
        return [];
    }

    public function jsonSerialize(): array
    {
        return $this->modalView->jsonSerialize();
    }
}
