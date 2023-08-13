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
        $this->modalView = Kit::newModal()
            ->callbackId(self::callbackId())
            ->title("Failure! :(")
            ->clearOnClose(true)
            ->close("Close");

        $this->modalView->text($message);
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

    public function jsonSerialize()
    {
        return $this->modalView->jsonSerialize();
    }
}
