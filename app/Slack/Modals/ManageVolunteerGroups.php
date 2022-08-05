<?php

namespace App\Slack\Modals;


use App\Customer;
use App\Http\Requests\SlackRequest;
use App\Slack\SlackOptions;
use App\TrainableEquipment;
use App\VolunteerGroup;
use App\WooCommerce\Api\WooCommerceApi;
use Illuminate\Support\Facades\Log;
use SlackPhp\BlockKit\Kit;
use SlackPhp\BlockKit\Surfaces\Modal;

class ManageVolunteerGroups implements ModalInterface
{
    use ModalTrait;

    private Modal $modalView;

    private const GROUP = 'group';
    private const CREATE_NEW = 'create-new';

    public function __construct()
    {
        $this->modalView = Kit::newModal()
            ->callbackId(self::callbackId())
            ->title('Manage Volunteer Groups')
            ->clearOnClose(true)
            ->close('Cancel')
            ->submit('Submit');
    }

    public function initialView()
    {
        $this->modalView->newInput()
            ->blockId(self::GROUP)
            ->label('Group')
            ->newSelectMenu(self::GROUP)
            ->forExternalOptions()
            ->placeholder("Select Volunteer Group");

        $this->modalView->divider();

        $this->modalView->newSection()
            ->blockId(self::CREATE_NEW)
            ->mrkdwnText("Or, create a new volunteer group:")
            ->newButtonAccessory(self::CREATE_NEW)
            ->text("Create New");
    }

    public static function callbackId(): string
    {
        return 'manage-volunteer-groups-modal';
    }

    public static function handle(SlackRequest $request)
    {
        return response('');
    }

    public static function getOptions(SlackRequest $request)
    {
        $options = SlackOptions::new();

        /** @var VolunteerGroup $volunteerGroup */
        foreach (VolunteerGroup::all() as $volunteerGroup) {
            $options[$volunteerGroup->id] = $volunteerGroup->name;
        }

        return $options;
    }

    public function jsonSerialize()
    {
        return $this->modalView->jsonSerialize();
    }
}
