<?php

namespace App\External\Slack\Modals;

use App\External\WooCommerce\Api\WooCommerceApi;
use App\Http\Requests\SlackRequest;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use SlackPhp\BlockKit\Kit;

class ManageMembersCardsModal implements ModalInterface
{
    use ModalTrait;

    private const CARD_NUM = 'card-num';

    /**
     * @param  int  $customerId  The customer's Woo Commerce ID
     */
    public function __construct(int $customerId)
    {
        /** @var Customer $customer */
        $customer = Customer::find($customerId);

        $cardString = $customer->cards
            ->where('member_has_card', true)
            ->implode('number', ',');

        $this->modalView = Kit::modal(
            title: "Manage a member's cards",
            callbackId: self::callbackId(),
            clearOnClose: true,
            close: 'Cancel',
            submit: 'Submit',
            privateMetadata: $customerId,
            blocks: [
                Kit::input(
                    label: 'Card Number (comma separated)',
                    blockId: self::CARD_NUM,
                    element: Kit::plainTextInput(
                        actionId: self::CARD_NUM,
                        placeholder: 'Enter Card Number',
                        initialValue: $cardString
                    ),
                ),
            ],
        );
    }

    public static function callbackId(): string
    {
        return 'manage-members-cards-modal';
    }

    public static function handle(SlackRequest $request): JsonResponse
    {
        if (! $request->customer()->canIDcheck()) {
            Log::warning('ManageMembersCardsModal: Rejecting unauthorized submission from user '.$request->customer()->id);
            throw new \Exception('Unauthorized');
        }

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
}
