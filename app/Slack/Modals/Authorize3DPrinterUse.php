<?php

namespace App\Slack\Modals;

use App\Customer;
use App\Http\Requests\SlackRequest;
use App\UserMembership;
use App\WooCommerce\Api\WooCommerceApi;
use Jeremeamia\Slack\BlockKit\Slack;
use Jeremeamia\Slack\BlockKit\Surfaces\Modal;

class Authorize3DPrinterUse implements ModalInterface
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
            ->title('3D Printer')
            ->clearOnClose(true)
            ->close('Cancel')
            ->privateMetadata($customerId);

        /** @var Customer $customer */
        $customer = Customer::whereWooId($customerId)->with('memberships')->first();

        if ($customer->hasMembership(UserMembership::MEMBERSHIP_3DP_USER)) {
            $this->modalView->text('They already have access to the 3d printers');
        } else {
            $introText = "This will let {$customer->first_name} {$customer->last_name} use the 3d printers. ".
                'They will be sent an email with generated OctoPrint credentials.';

            $this->modalView
                ->submit('Confirm')
                ->text($introText);
        }
    }

    public static function callbackId()
    {
        return 'authorize-3dp-use-modal';
    }

    public static function handle(SlackRequest $request)
    {
        $customerId = $request->payload()['view']['private_metadata'];

        $wooCommerceApi = app(WooCommerceApi::class);

        $wooCommerceApi->members->addMembership($customerId, UserMembership::MEMBERSHIP_3DP_USER);

        return (new SuccessModal())->update();
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
