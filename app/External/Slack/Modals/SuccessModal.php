<?php

namespace App\External\Slack\Modals;

use App\Http\Requests\SlackRequest;
use Illuminate\Http\JsonResponse;
use SlackPhp\BlockKit\Kit;

class SuccessModal implements ModalInterface
{
    use ModalTrait;

    public function __construct($message)
    {
        $this->modalView = Kit::modal(
            title: 'Success!',
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
        return 'success-modal';
    }

    public static function handle(SlackRequest $request): JsonResponse
    {
        return self::clearViewStack();
    }
}
