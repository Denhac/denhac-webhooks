<?php

namespace App\Slack\Modals;

use App\Customer;
use App\Http\Requests\SlackRequest;
use App\UserMembership;
use App\WinDSX\Door;
use App\WooCommerce\Api\WooCommerceApi;
use Illuminate\Support\Facades\Log;
use Jeremeamia\Slack\BlockKit\Inputs\TimePicker;
use Jeremeamia\Slack\BlockKit\Kit;
use Jeremeamia\Slack\BlockKit\Partials\Option;
use Jeremeamia\Slack\BlockKit\Surfaces\Modal;

class ManageOpenHouseModal implements ModalInterface
{
    use ModalTrait;

//    private const EXPIRES_TIME_BLOCK_ID = 'expires-time-block';
    private const EXPIRES_TIME_ACTION_ID = 'expires-time-action';
    private const DOORS_BLOCK_ID = 'doors-block-id';
    private const DOORS_ACTION_ID = 'doors-action-id';

    /**
     * @var Modal
     */
    private Modal $modalView;

    /**
     * ManageMembersCardsModal constructor.
     */
    public function __construct()
    {
        $this->modalView = Kit::newModal()
            ->callbackId(self::callbackId())
            ->title('Manage Open House Doors')
            ->clearOnClose(true)
            ->close('Cancel');

        $timePicker = (new TimePicker())
            ->initialTime("23:00")
            ->actionId(self::EXPIRES_TIME_ACTION_ID);

        $this->modalView->newSection()
            ->mrkdwnText("When should these doors close?")
            ->setAccessory($timePicker);

        $checkboxes = $this->modalView
            ->newInput()
            ->blockId(self::DOORS_BLOCK_ID)
            ->newCheckboxes(self::DOORS_ACTION_ID);

        /** @var Door $door */
        foreach (Door::all() as $door) {
            $option = Option::new($door->humanReadableName, "device-".$door->dsxDeviceId);
            $checkboxes->addOption($option, $door->openDuringOpenHouseByDefault);
        }
    }

    public static function callbackId()
    {
        return 'manage-open-house-modal';
    }

    public static function handle(SlackRequest $request)
    {
        Log::info("Manage open house modal: " . print_r($request->payload(), true));

        return self::clearViewStack();
    }

    public static function getOptions(SlackRequest $request)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->modalView->jsonSerialize();
    }
}
