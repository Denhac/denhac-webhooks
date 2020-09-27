<?php

namespace App\Slack\Modals;


use App\Http\Requests\SlackRequest;
use App\Slack\SlackOptions;
use App\UserMembership;
use Jeremeamia\Slack\BlockKit\Slack;
use Jeremeamia\Slack\BlockKit\Surfaces\Modal;

class MembershipOptionsModal implements ModalInterface
{
    use ModalTrait;

    private const MEMBERSHIP_OPTION_BLOCK_ID = 'membership-option-block';
    private const MEMBERSHIP_OPTION_ACTION_ID = 'membership-option-action';
    private const CANCEL_MEMBERSHIP_VALUE = 'value-cancel-membership';
    private const SIGN_UP_NEW_MEMBER_VALUE = 'value-sign-up-new-member';
    private const MANAGE_MEMBERS_CARDS_VALUE = 'value-manage-members-cards';

    /**
     * @var Modal
     */
    private $modalView;

    public function __construct()
    {
        $this->modalView = Slack::newModal()
            ->callbackId(self::callbackId())
            ->title("What do you want to do?")
            ->clearOnClose(true)
            ->close("Cancel")
            ->submit("Submit");

        $this->modalView->newInput()
            ->label("Membership Option")
            ->blockId(self::MEMBERSHIP_OPTION_BLOCK_ID)
            ->newSelectMenu()
            ->forExternalOptions()
            ->actionId(self::MEMBERSHIP_OPTION_ACTION_ID)
            ->placeholder("Select an Item")
            ->minQueryLength(0);
    }

    public static function callbackId()
    {
        return 'membership-command-modal';
    }

    public static function handle(SlackRequest $request)
    {
        $selectedOption = $request->payload()['view']['state']['values']
        [self::MEMBERSHIP_OPTION_BLOCK_ID][self::MEMBERSHIP_OPTION_ACTION_ID]
        ['selected_option']['value'];

        switch ($selectedOption) {
            case self::SIGN_UP_NEW_MEMBER_VALUE:
                $modal = new NeedIdCheckModal();
                return $modal->push();
            case self::MANAGE_MEMBERS_CARDS_VALUE:
                $modal = new SelectAMemberModal(ManageMembersCardsModal::class);
                return $modal->push();
            case self::CANCEL_MEMBERSHIP_VALUE:
                $modal = new CancelMembershipConfirmationModal($request->customer());
                return $modal->push();
        }

        throw new \Exception("Slack membership model had unknown selected option: $selectedOption");
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

        if(is_null($customer)) {
            return $options;
        }

        if($customer->hasCapability('denhac_can_verify_member_id')) {
            $options->option("Sign up new member", self::SIGN_UP_NEW_MEMBER_VALUE);
            $options->option("Manage a member's access cards", self::MANAGE_MEMBERS_CARDS_VALUE);
        }

        if($customer->hasMembership(UserMembership::MEMBERSHIP_3DP_TRAINER)) {
            $options->option("Authorize a member to use the 3d printer", 'lolz');
        }

        $subscriptions = $customer->subscriptions;
        $hasActiveMembership = $subscriptions->where('status', 'active')->count() > 0;

        if($hasActiveMembership) {
            $options->option("Cancel My Membership", self::CANCEL_MEMBERSHIP_VALUE);
        }

        return $options;
    }
}
