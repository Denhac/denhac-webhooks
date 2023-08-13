<?php

namespace App\External\Slack\Modals;

use App\External\Slack\SlackOptions;
use App\External\WinDSX\Door;
use App\Http\Requests\SlackRequest;
use App\UserMembership;
use SlackPhp\BlockKit\Kit;
use SlackPhp\BlockKit\Surfaces\Modal;

class MembershipOptionsModal implements ModalInterface
{
    use ModalTrait;

    private const MEMBERSHIP_OPTION = 'membership-option';
    private const CANCEL_MEMBERSHIP_VALUE = 'value-cancel-membership';
    private const SIGN_UP_NEW_MEMBER_VALUE = 'value-sign-up-new-member';
    private const MANAGE_MEMBERS_CARDS_VALUE = 'value-manage-members-cards';
    private const MANAGE_OPEN_HOUSE_VALUE = 'value-manage-open-house-doors';
    private const QUICK_OPEN_HOUSE_VALUE = 'value-quick-open-house';
    private const ALL_DOORS_DEFAULT_VALUE = 'value-all-doors-default';
    private const CREATE_TRAINABLE_EQUIPMENT_VALUE = 'value-create-trainable-equipment';
    private const EQUIPMENT_AUTHORIZATION_VALUE = 'value-equipment-authorization';
    private const MANAGE_VOLUNTEER_GROUPS = 'value-manage-volunteer-groups';

    private const COUNTDOWN_TEST_VALUE = 'value-countdown-test';

    private Modal $modalView;

    public function __construct()
    {
        $this->modalView = Kit::newModal()
            ->callbackId(self::callbackId())
            ->title('What do you want to do?')
            ->clearOnClose(true)
            ->close('Cancel')
            ->submit('Submit');

        $this->modalView->newInput()
            ->label('Membership Option')
            ->blockId(self::MEMBERSHIP_OPTION)
            ->newSelectMenu()
            ->forExternalOptions()
            ->actionId(self::MEMBERSHIP_OPTION)
            ->placeholder('Select an Item')
            ->minQueryLength(0);
    }

    public static function callbackId()
    {
        return 'membership-command-modal';
    }

    public static function handle(SlackRequest $request)
    {
        $selectedOption = $request->payload()['view']['state']['values'][self::MEMBERSHIP_OPTION][self::MEMBERSHIP_OPTION]['selected_option']['value'];

        switch ($selectedOption) {
            case self::SIGN_UP_NEW_MEMBER_VALUE:
                $modal = new NeedIdCheckModal();
                break;
            case self::MANAGE_MEMBERS_CARDS_VALUE:
                $modal = new SelectAMemberModal(ManageMembersCardsModal::class);
                break;
            case self::MANAGE_OPEN_HOUSE_VALUE:
                $modal = new ManageOpenHouseModal();
                break;
            case self::QUICK_OPEN_HOUSE_VALUE:
                Door::quickOpenHouse();
                return self::clearViewStack();
            case self::ALL_DOORS_DEFAULT_VALUE:
                Door::quickDefaultDoors();
                return self::clearViewStack();
            case self::CANCEL_MEMBERSHIP_VALUE:
                $modal = new CancelMembershipConfirmationModal($request->customer());
                break;
            case self::CREATE_TRAINABLE_EQUIPMENT_VALUE:
                $modal = new CreateTrainableEquipment($request->customer());
                break;
            case self::EQUIPMENT_AUTHORIZATION_VALUE:
                $modal = new EquipmentAuthorization();
                break;
            case self::COUNTDOWN_TEST_VALUE:
                $modal = new CountdownTestModal(null);
                break;
            case self::MANAGE_VOLUNTEER_GROUPS:
                $modal = new ManageVolunteerGroups();
                $modal->initialView();
                break;
            default:
                throw new \Exception("Slack membership model had unknown selected option: $selectedOption");
        }

        return $modal->update();
    }

    public function jsonSerialize()
    {
        return $this->modalView->jsonSerialize();
    }

    public static function getOptions(SlackRequest $request)
    {
        $options = SlackOptions::new();

        $customer = $request->customer()
            ->load(['subscriptions', 'memberships']);

        if (is_null($customer)) {
            return $options;
        }

        if ($customer->canIDCheck()) {
            $options->option('Sign up new member', self::SIGN_UP_NEW_MEMBER_VALUE);
            $options->option('Manage a member\'s access cards', self::MANAGE_MEMBERS_CARDS_VALUE);

            $options->option('Quick Open House', self::QUICK_OPEN_HOUSE_VALUE);
            $options->option('All doors to default state', self::ALL_DOORS_DEFAULT_VALUE);
            $options->option('Manage Open House doors', self::MANAGE_OPEN_HOUSE_VALUE);
        }

        if ($customer->isABoardMember() || $customer->isAManager()) {
            $options->option('Manage Volunteer Group', self::MANAGE_VOLUNTEER_GROUPS);
        }

        if ($customer->isATrainer()) {
            $options->option('Equipment Authorization', self::EQUIPMENT_AUTHORIZATION_VALUE);
        }

        if ($customer->hasMembership(UserMembership::MEMBERSHIP_META_TRAINER)) {
            $options->option('Create Trainable Equipment', self::CREATE_TRAINABLE_EQUIPMENT_VALUE);
        }

        $subscriptions = $customer->subscriptions;
        $hasActiveMembership = $subscriptions->where('status', 'active')->count() > 0;

//        if ($hasActiveMembership) {
//            $options->option('Cancel My Membership', self::CANCEL_MEMBERSHIP_VALUE);
//        }

        return $options;
    }
}
