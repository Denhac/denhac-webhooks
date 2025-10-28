<?php

namespace App\External\Slack\Modals;

use App\External\WinDSX\Door;
use App\Http\Requests\SlackRequest;
use App\Models\Customer;
use App\Models\UserMembership;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use SlackPhp\BlockKit\Collections\OptionSet;
use SlackPhp\BlockKit\Kit;

class MembershipOptionsModal implements ModalInterface
{
    use ModalTrait;

    private const MEMBERSHIP_OPTION = 'membership-option';

    private const SIGN_UP_NEW_MEMBER_VALUE = 'value-sign-up-new-member';

    private const MANAGE_MEMBERS_CARDS_VALUE = 'value-manage-members-cards';

    private const MANAGE_OPEN_HOUSE_VALUE = 'value-manage-open-house-doors';

    private const QUICK_OPEN_HOUSE_VALUE = 'value-quick-open-house';

    private const ALL_DOORS_DEFAULT_VALUE = 'value-all-doors-default';

    private const CREATE_TRAINABLE_EQUIPMENT_VALUE = 'value-create-trainable-equipment';

    private const EQUIPMENT_AUTHORIZATION_VALUE = 'value-equipment-authorization';

    private const MANAGE_VOLUNTEER_GROUPS = 'value-manage-volunteer-groups';

    private const COUNTDOWN_TEST_VALUE = 'value-countdown-test';

    public function __construct(?Customer $customer)
    {
        $membershipOptions = self::getMembershipOptions($customer);

        $this->modalView = Kit::modal(
            title: 'What do you want to do?',
            callbackId: self::callbackId(),
            clearOnClose: true,
            close: 'Cancel',
            submit: 'Submit',
            blocks: [
                Kit::input(
                    label: 'Membership Option',
                    blockId: self::MEMBERSHIP_OPTION,
                    element: Kit::staticSelectMenu(
                        actionId: self::MEMBERSHIP_OPTION,
                        placeholder: 'Select an item',
                        options: $membershipOptions,
                    ),
                )
            ]
        );

    }

    public static function callbackId(): string
    {
        return 'membership-command-modal';
    }

    public static function handle(SlackRequest $request): JsonResponse
    {
        $selectedOption = $request->payload()['view']['state']['values'][self::MEMBERSHIP_OPTION][self::MEMBERSHIP_OPTION]['selected_option']['value'];

        switch ($selectedOption) {
            case self::SIGN_UP_NEW_MEMBER_VALUE:
                $modal = new NeedIdCheckModal;
                break;
            case self::MANAGE_MEMBERS_CARDS_VALUE:
                $modal = new SelectAMemberModal(ManageMembersCardsModal::class);
                break;
            case self::MANAGE_OPEN_HOUSE_VALUE:
                $modal = new ManageOpenHouseModal;
                break;
            case self::QUICK_OPEN_HOUSE_VALUE:
                if (! $request->customer()->canIDcheck()) {
                    Log::warning('QuickOpenHouse: Rejecting unauthorized submission from user ' . $request->customer()->id);
                    throw new \Exception('Unauthorized');
                }
                Door::quickOpenHouse();

                return self::clearViewStack();
            case self::ALL_DOORS_DEFAULT_VALUE:
                if (! $request->customer()->canIDcheck()) {
                    Log::warning('QuickOpenHouse: Rejecting unauthorized submission from user ' . $request->customer()->id);
                    throw new \Exception('Unauthorized');
                }
                Door::quickDefaultDoors();

                return self::clearViewStack();
            case self::CREATE_TRAINABLE_EQUIPMENT_VALUE:
                $modal = new CreateTrainableEquipment($request->customer());
                break;
            case self::EQUIPMENT_AUTHORIZATION_VALUE:
                $modal = new EquipmentAuthorization($request->customer());
                break;
            case self::COUNTDOWN_TEST_VALUE:
                $modal = new CountdownTestModal(null);
                break;
            case self::MANAGE_VOLUNTEER_GROUPS:
                $modal = new ManageVolunteerGroups;
                $modal->initialView();
                break;
            default:
                throw new \Exception("Slack membership model had unknown selected option: $selectedOption");
        }

        return $modal->update();
    }

    private static function getMembershipOptions(?Customer $customer): OptionSet
    {
        $optionSet = Kit::optionSet();

        if (is_null($customer)) {
            return $optionSet;
        }

        $customer->load(['subscriptions', 'memberships']);

        if ($customer->canIDCheck()) {
            $optionSet->append(Kit::option('Sign up new member', self::SIGN_UP_NEW_MEMBER_VALUE));
            $optionSet->append(Kit::option('Manage a member\'s access cards', self::MANAGE_MEMBERS_CARDS_VALUE));

            $optionSet->append(Kit::option('Quick Open House', self::QUICK_OPEN_HOUSE_VALUE));
            $optionSet->append(Kit::option('All doors to default state', self::ALL_DOORS_DEFAULT_VALUE));
            $optionSet->append(Kit::option('Manage Open House doors', self::MANAGE_OPEN_HOUSE_VALUE));
        }

        if ($customer->isABoardMember() || $customer->isAManager()) {
            $optionSet->append(Kit::option('Manage Volunteer Group', self::MANAGE_VOLUNTEER_GROUPS));
        }

        if ($customer->isATrainer()) {
            $optionSet->append(Kit::option('Equipment Authorization', self::EQUIPMENT_AUTHORIZATION_VALUE));
        }

        if ($customer->hasMembership(UserMembership::MEMBERSHIP_META_TRAINER)) {
            $optionSet->append(Kit::option('Create Trainable Equipment', self::CREATE_TRAINABLE_EQUIPMENT_VALUE));
        }

        // If there's only 1 option, automatically select it.
        if($optionSet->count() == 1) {
            $option = $optionSet->offsetGet(0);
            $option->initial(true);
        }

        return $optionSet;
    }
}
