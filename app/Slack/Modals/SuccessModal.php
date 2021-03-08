<?php

namespace App\Slack\Modals;


use App\Http\Requests\SlackRequest;
use Jeremeamia\Slack\BlockKit\Kit;
use Jeremeamia\Slack\BlockKit\Surfaces\Modal;

class SuccessModal implements ModalInterface
{
    use ModalTrait;

    /**
     * @var Modal
     */
    private $modalView;

    /**
     * ManageMembersCardsModal constructor.
     */
    public function __construct()
    {
        $this->modalView = Kit::newModal()
            ->callbackId(self::callbackId())
            ->title("Success!")
            ->clearOnClose(true)
            ->close("Close");

        $this->modalView->text(" ");
    }

    public static function callbackId()
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

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return $this->modalView->jsonSerialize();
    }
}
