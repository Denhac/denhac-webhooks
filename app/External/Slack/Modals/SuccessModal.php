<?php

namespace App\External\Slack\Modals;

use App\Http\Requests\SlackRequest;
use SlackPhp\BlockKit\Kit;
use SlackPhp\BlockKit\Surfaces\Modal;

class SuccessModal implements ModalInterface
{
    use ModalTrait;

    private Modal $modalView;

    public function __construct()
    {
        $this->modalView = Kit::modal(
            title: 'Success!',
            callbackId: self::callbackId(),
            clearOnClose: true,
            close: 'Close',
        );
    }

    public static function callbackId(): string
    {
        return 'success-modal';
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
