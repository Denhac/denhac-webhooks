<?php

namespace App\External\Slack\Modals;

use App\External\WooCommerce\Api\WooCommerceApi;
use App\Http\Requests\SlackRequest;
use App\Models\Customer;
use App\Notifications\IdCheckedWithNoWaiver;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use SlackPhp\BlockKit\Kit;
use SlackPhp\BlockKit\Surfaces\Modal;

class NewMemberIdCheckModal implements ModalInterface
{
    use ModalTrait;

    private const FIRST_NAME = 'first-name';

    private const LAST_NAME = 'last-name';

    private const BIRTHDAY = 'birthday';

    private const CARD_NUM = 'card-num';

    private Modal $modalView;

    public function __construct($customer_id)
    {
        /** @var Customer $customer */
        $customer = Customer::find($customer_id);

        if ($customer->hasSignedMembershipWaiver()) {
            $membershipWaiverSection = Kit::section(
                text: Kit::mrkdwnText(':white_check_mark: Waiver found'),
            );
        } else {
            $membershipWaiverSection = Kit::section(
                text: Kit::mrkdwnText(':x: No waiver found.'),  # TODO see next page after ID check
            );
        }

        $initialBirthday = $customer->birthday?->format('Y-m-d');
        $initialCardString = $customer->cards?->implode('number', ',');

        $this->modalView = Kit::modal(
            title: 'New Member Signup',
            callbackId: self::callbackId(),
            clearOnClose: true,
            close: 'Cancel',
            submit: 'Submit',
            privateMetadata: $customer->id,
            blocks: [
                $membershipWaiverSection,
                Kit::input(
                    label: 'First Name',
                    blockId: self::FIRST_NAME,
                    element: Kit::plainTextInput(
                        actionId: self::FIRST_NAME,
                        initialValue: $customer->first_name,
                    ),
                ),
                Kit::input(
                    label: 'Last Name',
                    blockId: self::LAST_NAME,
                    element: Kit::plainTextInput(
                        actionId: self::LAST_NAME,
                        initialValue: $customer->last_name,
                    ),
                ),
                Kit::input(
                    label: 'Birthday',
                    blockId: self::BIRTHDAY,
                    element: Kit::datePicker(
                        actionId: self::BIRTHDAY,
                        initialDate: $initialBirthday,
                    ),
                ),
                Kit::input(
                    label: 'Card Number',
                    blockId: self::CARD_NUM,
                    element: Kit::plainTextInput(
                        actionId: self::CARD_NUM,
                        placeholder: 'Enter Card Number',
                        initialValue: $initialCardString,
                    ),
                ),
                Kit::section(
                    text: Kit::plainText(
                        'The numbers on the card will look like either "12345 3300687-1" or "175-012345" and ' .
                        'you should enter "12345" in this field.',
                    ),
                ),
            ],
        );
    }

    public static function callbackId(): string
    {
        return 'membership-new-member-id-check-modal';
    }

    public static function handle(SlackRequest $request)
    {
        $view = $request->payload()['view'];
        $viewId = $view['id'];
        $firstName = $view['state']['values'][self::FIRST_NAME][self::FIRST_NAME]['value'];
        $lastName = $view['state']['values'][self::LAST_NAME][self::LAST_NAME]['value'];
        $birthday = Carbon::parse($view['state']['values'][self::BIRTHDAY][self::BIRTHDAY]['selected_date']);
        $card = $view['state']['values'][self::CARD_NUM][self::CARD_NUM]['value'];

        $errors = [];

        if ($birthday > Carbon::today()->subYears(18)) {
            $errors[self::BIRTHDAY] = 'New member is not at least 18';
        }

        if (preg_match("/^\d+$/", $card) == 0) {
            $errors[self::CARD_NUM] = 'Card should be a number';
        }

        if (! empty($errors)) {
            return response()->json([
                'response_action' => 'errors',
                'errors' => $errors,
            ]);
        }

        $customerId = $view['private_metadata'];
        /** @var Customer $customer */
        $customer = Customer::find($customerId);

        $wooCommerceApi = app(WooCommerceApi::class);

        $idChecker = $request->customer();
        $wooCommerceApi->customers
            ->update($customerId, [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'meta_data' => [
                    [
                        'key' => 'access_card_number',
                        'value' => $card,
                    ],
                    [
                        'key' => 'account_birthday',
                        'value' => $birthday->format('Y-m-d'),
                    ],
                    [
                        'key' => 'id_was_checked_by',
                        'value' => $idChecker->id,
                    ],
                    [
                        'key' => 'id_was_checked_when',
                        'value' => Carbon::now(),
                    ],
                    [
                        'key' => 'id_was_checked',
                        'value' => true,
                    ],
                ],
            ]);

        if (! $customer->hasSignedMembershipWaiver()) {
            Notification::route('mail', $customer->email)
                ->notify(new IdCheckedWithNoWaiver($customer));
            // TODO Pop something up so id checker can match the waiver
        }

        return (new NewMemberInfoModal)->update();
    }

    public static function getOptions(SlackRequest $request)
    {
        return [];
    }

    public function jsonSerialize(): array
    {
        return $this->modalView->jsonSerialize();
    }
}
