<?php

namespace App\External\Slack\Modals;

use App\External\Slack\SlackOptions;
use App\Http\Requests\SlackRequest;
use App\Models\VolunteerGroup;
use App\External\Slack\BlockActions\RespondsToBlockActions;
use SlackPhp\BlockKit\Kit;
use SlackPhp\BlockKit\Surfaces\Modal;

class ManageVolunteerGroups implements ModalInterface
{
    use ModalTrait;
    use RespondsToBlockActions;

    private Modal $modalView;

    private const GROUP_DROPDOWN = 'group-dropdown';
    private const CREATE_NEW_GROUP = 'create-new-group';

    private const GROUP_NAME = 'group-name';
    private const PLAN_ID = 'plan-id';

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
            ->blockId(self::GROUP_DROPDOWN)
            ->label('Group')
            ->newSelectMenu(self::GROUP_DROPDOWN)
            ->forExternalOptions()
            ->minQueryLength(0)
            ->placeholder("Select Volunteer Group");

        $this->modalView->divider();

        $this->modalView->newSection()
            ->blockId(self::CREATE_NEW_GROUP)
            ->mrkdwnText("Or, create a new volunteer group:")
            ->newButtonAccessory(self::CREATE_NEW_GROUP)
            ->text("Create New");
    }

    public function createForm()
    {
        $this->modalView->newInput()
            ->blockId(self::GROUP_NAME)
            ->label('Group name')
            ->newTextInput(self::GROUP_NAME)
            ->placeholder("Name");

        $this->modalView->newInput()
            ->blockId(self::PLAN_ID)
            ->label('Plan ID that grants access')
            ->newSelectMenu(self::PLAN_ID)
            ->forExternalOptions()
            ->minQueryLength(0);
    }

    public function groupForm()
    {
        $this->modalView->newSection()
            ->mrkdwnText("Sorry, nothing to see here");
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

        $blockId = $request->payload()['block_id'];

        if($blockId == self::GROUP_DROPDOWN) {
            /** @var VolunteerGroup $volunteerGroup */
            foreach (VolunteerGroup::all() as $volunteerGroup) {
                $options[$volunteerGroup->id] = $volunteerGroup->name;
            }
        }

        return $options;
    }

    public function jsonSerialize()
    {
        return $this->modalView->jsonSerialize();
    }

    public static function getBlockActions(): array
    {
        return [
            self::blockActionUpdate(self::GROUP_DROPDOWN),
            self::blockActionUpdate(self::CREATE_NEW_GROUP),
        ];
    }

    static function onBlockAction(SlackRequest $request)
    {
        $modal = new ManageVolunteerGroups();

        $action = $request->action();

        if($action == self::CREATE_NEW_GROUP) {
            $modal->createForm();
        } elseif ($action == self::GROUP_DROPDOWN) {
            $modal->groupForm();
        }

        return $modal->updateViaApi($request);
    }
}
