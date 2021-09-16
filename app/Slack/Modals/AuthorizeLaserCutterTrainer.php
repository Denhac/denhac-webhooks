<?php

namespace App\Slack\Modals;

use App\Customer;
use App\Http\Requests\SlackRequest;
use App\UserMembership;
use App\WooCommerce\Api\WooCommerceApi;
use Jeremeamia\Slack\BlockKit\Kit;
use Jeremeamia\Slack\BlockKit\Surfaces\Modal;

class AuthorizeLaserCutterTrainer implements ModalInterface
{
    use ModalTrait;

    private Modal $modalView;

    /**
     * @param int $customerId The customer's Woo Commerce ID
     */
    public function __construct(int $customerId)
    {
        $this->modalView = Kit::newModal()
            ->callbackId(self::callbackId())
            ->title('Laser Cutter')
            ->clearOnClose(true)
            ->close('Cancel')
            ->privateMetadata($customerId);

        /** @var Customer $customer */
        $customer = Customer::whereWooId($customerId)->with('memberships')->first();

        if ($customer->hasMembership(UserMembership::MEMBERSHIP_LASER_CUTTER_TRAINER)) {
            $this->modalView->text('They already are a trainer on the laser cutter');
        } else {
            $introText = "This will let {$customer->first_name} {$customer->last_name} train on the laser cutter. ".
                'They will be allowed to make others users and trainers as well.';

            $this->modalView
                ->submit('Confirm')
                ->text($introText);
        }
    }

    public static function callbackId(): string
    {
        return 'authorize-laser-cutter-trainer-modal';
    }

    public static function handle(SlackRequest $request)
    {
        $customerId = $request->payload()['view']['private_metadata'];

        $wooCommerceApi = app(WooCommerceApi::class);

        $wooCommerceApi->members->addMembership($customerId, UserMembership::MEMBERSHIP_LASER_CUTTER_TRAINER);

        return (new SuccessModal())->update();
    }

    public static function getOptions(SlackRequest $request)
    {
        return [];
    }

    public function jsonSerialize()
    {
        return $this->modalView->jsonSerialize();
    }
}
