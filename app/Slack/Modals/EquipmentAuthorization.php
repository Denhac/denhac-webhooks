<?php

namespace App\Slack\Modals;

use App\Http\Requests\SlackRequest;
use App\Slack\BlockActions\BlockActionInterface;
use Illuminate\Support\Facades\Log;
use SlackPhp\BlockKit\Kit;
use SlackPhp\BlockKit\Surfaces\Modal;

class EquipmentAuthorization implements ModalInterface
{
    use ModalTrait;

    private const EQUIPMENT_DROPDOWN = 'equipment-dropdown';
    private const PERSON_DROPDOWN = 'person-dropdown';
    private const TRAINER_CHECK = 'trainer-check';
    private const USER_CHECK = 'user-check';

    private Modal $modalView;

    public function __construct()
    {
        $this->modalView = Kit::newModal()
            ->callbackId(self::callbackId())
            ->title('Equipment Authorization')
            ->clearOnClose(true)
            ->close('Cancel')
            ->submit('Submit');

        $this->modalView->newInput()
            ->dispatchAction()
            ->blockId(self::EQUIPMENT_DROPDOWN)
            ->label("Equipment")
            ->newSelectMenu()
            ->forExternalOptions()
            ->actionId(self::EQUIPMENT_DROPDOWN)
            ->placeholder("Select equipment");

        $this->modalView->newInput()
            ->dispatchAction()
            ->blockId(self::PERSON_DROPDOWN)
            ->label("Person")
            ->newSelectMenu()
            ->forExternalOptions()
            ->actionId(self::PERSON_DROPDOWN)
            ->placeholder("Select a user");
    }

    public static function callbackId()
    {
        return 'equipment-authorization-modal';
    }

    public static function handle(SlackRequest $request)
    {
        Log::info("Equipment authorization");
        Log::info(print_r($request->payload(), true));

        return (new SuccessModal())->update();
    }

    /**
     * @return BlockActionInterface[]
     */
    public static function getBlockActions(): array
    {
        return [
            new class() implements BlockActionInterface {
                public static function blockId(): string {return "equipment-dropdown";}  // TODO find a way to not duplicate
                public static function actionId(): string {return "equipment-dropdown";}  // TODO find a way to not duplicate

                public static function handle(SlackRequest $request)
                {
                    return response('');  // Do nothing, accept the action.
                }
            },
            new class() implements BlockActionInterface {
                public static function blockId(): string {return "person-dropdown";}  // TODO find a way to not duplicate
                public static function actionId(): string {return "person-dropdown";}  // TODO find a way to not duplicate

                public static function handle(SlackRequest $request)
                {
                    return response('');  // Do nothing, accept the action.
                }
            }
        ];
    }

    public static function getOptions(SlackRequest $request)
    {
        Log::info("Equipment auth options request");
        Log::info(print_r($request->payload(), true));
        return [];
    }

    public function jsonSerialize()
    {
        return $this->modalView->jsonSerialize();
    }
}
