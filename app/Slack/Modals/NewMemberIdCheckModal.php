<?php

namespace App\Slack\Modals;


use App\Http\Requests\SlackRequest;
use App\Subscription;
use App\WooCommerce\Api\WooCommerceApi;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Jeremeamia\Slack\BlockKit\Slack;
use Jeremeamia\Slack\BlockKit\Surfaces\Modal;

class NewMemberIdCheckModal implements ModalInterface
{
    use ModalTrait;

    private const FIRST_NAME_BLOCK_ID = 'first-name-block';
    private const FIRST_NAME_ACTION_ID = 'first-name-action';
    private const LAST_NAME_BLOCK_ID = 'last-name-block';
    private const LAST_NAME_ACTION_ID = 'last-name-action';
    private const BIRTHDAY_BLOCK_ID = 'birthday-block';
    private const BIRTHDAY_ACTION_ID = 'birthday-action';
    private const CARD_NUM_BLOCK_ID = 'card-num-block';
    private const CARD_NUM_ACTION_ID = 'card-num-action';

    /**
     * @var Modal
     */
    private $modalView;

    public function __construct($subscription_id)
    {        /** @var Subscription $subscription */
        $subscription = Subscription::findOrFail($subscription_id);
        $customer = $subscription->customer;

        $this->modalView = Slack::newModal()
            ->callbackId(self::callbackId())
            ->title("New Member Signup")
            ->submit("Submit")
            ->privateMetadata($customer->woo_id);

        $this->modalView->newInput()
            ->blockId(self::FIRST_NAME_BLOCK_ID)
            ->label("First Name")
            ->newTextInput(self::FIRST_NAME_ACTION_ID)
            ->initialValue($customer->first_name);

        $this->modalView->newInput()
            ->blockId(self::LAST_NAME_BLOCK_ID)
            ->label("Last Name")
            ->newTextInput(self::LAST_NAME_ACTION_ID)
            ->initialValue($customer->last_name);

        $birthdayInput = $this->modalView->newInput()
            ->blockId(self::BIRTHDAY_BLOCK_ID)
            ->label("Birthday")
            ->newDatePicker(self::BIRTHDAY_ACTION_ID);

        if (!is_null($customer->birthday)) {
            $birthdayInput->initialDate($customer->birthday->format('Y-m-d'));
        }

        $cardsInput = $this->modalView->newInput()
            ->blockId(self::CARD_NUM_BLOCK_ID)
            ->label("Card Number")
            ->newTextInput(self::CARD_NUM_ACTION_ID)
            ->placeholder("Enter Card Number");

        $cardString = $customer->cards->implode('number', ',');
        if (! empty($cardString)) {
            $cardsInput->initialValue($cardString);
        }
    }

    public static function callbackId()
    {
        return 'membership-new-member-id-check-modal';
    }

    public static function handle(SlackRequest $request)
    {
        Log::info("Payload:");
        Log::info($request->get('payload'));
        $firstName = $request->payload()['view']['state']['values']
        [self::FIRST_NAME_BLOCK_ID][self::FIRST_NAME_ACTION_ID]['value'];
        $lastName = $request->payload()['view']['state']['values']
        [self::LAST_NAME_BLOCK_ID][self::LAST_NAME_ACTION_ID]['value'];
        $birthday = Carbon::parse($request->payload()['view']['state']['values']
        [self::BIRTHDAY_BLOCK_ID][self::BIRTHDAY_ACTION_ID]['selected_date']);
        $cards = $request->payload()['view']['state']['values']
        [self::CARD_NUM_BLOCK_ID][self::CARD_NUM_ACTION_ID]['value'];

        $errors = [];

        if($birthday > Carbon::today()->subYears(18)) {
            $errors[self::BIRTHDAY_BLOCK_ID] = "New member is not at least 18";
        }

        foreach(explode(",", $cards) as $card) {
            if (preg_match("/^\d+$/", $card) == 0) {
                $errors[self::CARD_NUM_BLOCK_ID] = "This is a comma separated list of cards (no spaces!)";
            }
        }

        if(!empty($errors)) {
            return response()->json([
                "response_action" => "errors",
                "errors" => $errors,
            ]);
        }

        $customerId = $request->payload()['view']['private_metadata'];
        Log::info($customerId);
        Log::info($firstName);
        Log::info($lastName);
        Log::info($birthday);
        Log::info($cards);

        $wooCommerceApi = app(WooCommerceApi::class);

        $wooCommerceApi->customers
            ->update($customerId, [
                "first_name" => $firstName,
                "last_name" => $lastName,
                "meta_data" => [
                    [
                        "key" => "access_card_number",
                        "value" => $cards,
                    ],
                    [
                        "key" => "account_birthday",
                        "value" => $birthday,
                    ]
                ],
            ]);

        return response()->json([
            "response_action" => "clear",
        ]);
    }

    public static function getOptions(SlackRequest $request)
    {
        // No options on this modal
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
