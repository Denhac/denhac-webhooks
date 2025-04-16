<?php

namespace App\External\Slack\Modals;

use App\Http\Requests\SlackRequest;
use App\Models\VolunteerGroup;
use Illuminate\Http\JsonResponse;
use SlackPhp\BlockKit\Kit;

class ManageVolunteerGroups implements ModalInterface
{
    use ModalTrait;

    private const GROUP = 'group';

    private const CREATE_NEW = 'create-new';

    public function __construct()
    {
        $this->modalView = Kit::modal(
            title: 'Manage Volunteer Groups',
            callbackId: self::callbackId(),
            clearOnClose: true,
            close: 'Cancel',
            submit: 'Submit',
            blocks: [
                Kit::section(
                    text: 'This feature is not yet implemented.',
                ),
            ]
        );
    }

    public function initialView(): void
    {
        $volunteerGroups = Kit::optionSet();

        /** @var VolunteerGroup $volunteerGroup */
        foreach (VolunteerGroup::all() as $volunteerGroup) {
            $volunteerGroups->append(Kit::option(
                text: $volunteerGroup->name,
                value: $volunteerGroup->id,
            ));
        }

        $this->modalView->blocks(
            Kit::input(
                label: 'Group',
                blockId: self::GROUP,
                element: Kit::staticSelectMenu(
                    actionId: self::GROUP,
                    placeholder: 'Select Volunteer Group',
                    options: $volunteerGroups,
                ),
            ),
            Kit::divider(),
            Kit::section(
                text: 'Or, create a new volunteer group:',
                blockId: self::CREATE_NEW,
                accessory: Kit::button(
                    text: 'Create new',
                    actionId: self::CREATE_NEW,
                ),
            ),
        );
    }

    public static function callbackId(): string
    {
        return 'manage-volunteer-groups-modal';
    }

    public static function handle(SlackRequest $request): JsonResponse
    {
        return response()->json();
    }
}
