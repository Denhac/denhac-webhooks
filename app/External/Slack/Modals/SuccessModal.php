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
        $this->modalView = Kit::newModal()
            ->callbackId(self::callbackId())
            ->title('Success!')
            ->clearOnClose(true)
            ->close('Close');

        $this->modalView->text(' ');
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

    public function jsonSerialize()
    {
        return $this->modalView->jsonSerialize();
    }
}
