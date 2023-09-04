<?php

namespace App\External\Slack\Modals;

use App\Customer;
use App\External\WooCommerce\Api\WooCommerceApi;
use App\Http\Requests\SlackRequest;
use SlackPhp\BlockKit\Kit;
use SlackPhp\BlockKit\Surfaces\Modal;

class ManageMembersCardsModal implements ModalInterface
{
    use ModalTrait;

    private const CARD_NUM = 'card-num';

    private Modal $modalView;

    /**
     * @param  int  $customerId The customer's Woo Commerce ID
     */
    public function __construct(int $customerId)
    {
        $this->modalView = Kit::newModal()
            ->callbackId(self::callbackId())
            ->title("Manage a member's cards")
            ->clearOnClose(true)
            ->close('Cancel')
            ->submit('Submit')
            ->privateMetadata($customerId);

        /** @var Customer $customer */
        $customer = Customer::whereWooId($customerId)->first();

        $cardsInput = $this->modalView->newInput()
            ->blockId(self::CARD_NUM)
            ->label('Card Number (comma separated)')
            ->newTextInput(self::CARD_NUM)
            ->placeholder('Enter Card Number');

        $cardString = $customer->cards
            ->where('member_has_card', true)
            ->implode('number', ',');
        if (! empty($cardString)) {
            $cardsInput->initialValue($cardString);
        }
    }

    public static function callbackId()
    {
        return 'manage-members-cards-modal';
    }

    public static function handle(SlackRequest $request)
    {
        $cards = $request->payload()['view']['state']['values'][self::CARD_NUM][self::CARD_NUM]['value'];

        $errors = [];

        foreach (explode(',', $cards) as $card) {
            if (preg_match("/^\d+$/", $card) == 0) {
                $errors[self::CARD_NUM] = 'This is a comma separated list of cards (no spaces!)';
            }
        }

        if (! empty($errors)) {
            return response()->json([
                'response_action' => 'errors',
                'errors' => $errors,
            ]);
        }

        $customerId = $request->payload()['view']['private_metadata'];

        $wooCommerceApi = app(WooCommerceApi::class);

        $wooCommerceApi->customers
            ->update($customerId, [
                'meta_data' => [
                    [
                        'key' => 'access_card_number',
                        'value' => $cards,
                    ],
                ],
            ]);

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
