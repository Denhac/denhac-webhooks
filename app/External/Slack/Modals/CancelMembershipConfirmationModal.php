<?php

namespace App\External\Slack\Modals;

use App\Models\Customer;
use App\External\WooCommerce\Api\WooCommerceApi;
use App\Http\Requests\SlackRequest;
use App\Models\Subscription;
use SlackPhp\BlockKit\Kit;
use SlackPhp\BlockKit\Surfaces\Modal;

class CancelMembershipConfirmationModal implements ModalInterface
{
    use ModalTrait;

    private Modal $modalView;

    public function __construct(Customer $customer)
    {
        $this->modalView = Kit::newModal()
            ->callbackId(self::callbackId())
            ->title('Confirm Cancellation')
            ->clearOnClose(true)
            ->close('No')
            ->submit('Yes')
            ->privateMetadata($customer->woo_id);

        $this->modalView->newSection()
            ->plainText('Are you sure you want to cancel?');
    }

    public static function callbackId()
    {
        return 'cancel-membership-confirmation-modal';
    }

    public static function handle(SlackRequest $request)
    {
        $customerId = $request->payload()['view']['private_metadata'];
        /** @var Customer $customer */
        $customer = Customer::whereWooId($customerId)->first();
        $activeSubscriptions = $customer->subscriptions
            ->where('status', 'active');

        $wooCommerceApi = app(WooCommerceApi::class);

        foreach ($activeSubscriptions as $subscription) {
            /* @var Subscription $subscription */

            $wooCommerceApi->subscriptions
                ->update($subscription->woo_id, [
                    'status' => 'pending-cancel',
                ]);
        }

        return self::clearViewStack();
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
