<?php

namespace App\Slack\Modals;


use App\Customer;
use App\Http\Requests\SlackRequest;
use App\UserMembership;
use App\WooCommerce\Api\WooCommerceApi;
use Jeremeamia\Slack\BlockKit\Slack;
use Jeremeamia\Slack\BlockKit\Surfaces\Modal;

class AuthorizeLaserCutterUse implements ModalInterface
{
    use ModalTrait;

    /**
     * @var Modal
     */
    private $modalView;

    /**
     * ManageMembersCardsModal constructor.
     * @param int $customerId The customer's Woo Commerce ID
     */
    public function __construct(int $customerId)
    {
        $this->modalView = Slack::newModal()
            ->callbackId(self::callbackId())
            ->title("Laser Cutter")
            ->clearOnClose(true)
            ->close("Cancel")
            ->privateMetadata($customerId);

        /** @var Customer $customer */
        $customer = Customer::whereWooId($customerId)->with('memberships')->first();

        if($customer->hasMembership(UserMembership::MEMBERSHIP_LASER_CUTTER_USER)) {
            $this->modalView->text("They already have access to the laser cutter");
        } else {
            $introText = "This will let {$customer->first_name} {$customer->last_name} use the laser cutter. " .
                "They will be sent an email letting them know.";

            $this->modalView
                ->submit("Confirm")
                ->text($introText);
        }
    }

    public static function callbackId()
    {
        return 'authorize-laser-cutter-use-modal';
    }

    public static function handle(SlackRequest $request)
    {
        $customerId = $request->payload()['view']['private_metadata'];

        $wooCommerceApi = app(WooCommerceApi::class);

        $wooCommerceApi->members->addMembership($customerId, UserMembership::MEMBERSHIP_LASER_CUTTER_USER);

        return (new SuccessModal())->push();
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
