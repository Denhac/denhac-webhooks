<?php

namespace App\External\Slack\Modals;

use App\Actions\WordPress\IdCheckMember;
use App\External\Slack\BlockActions\RespondsToBlockActions;
use App\Http\Requests\SlackRequest;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use SlackPhp\BlockKit\Kit;

class NewMemberIdCheckModal implements ModalInterface
{
    use ModalTrait;
    use RespondsToBlockActions;

    private const FIRST_NAME = 'first-name';

    private const LAST_NAME = 'last-name';

    private const BIRTHDAY = 'birthday';

    private const CARD_NUM = 'card-num';

    private const REFRESH_ACTION = 'refresh';

    public function __construct($customer_id)
    {
        /** @var Customer $customer */
        $customer = Customer::find($customer_id);

        if ($customer->hasSignedMembershipWaiver()) {
            $initialBirthday = $customer->birthday?->format('Y-m-d');
            $initialCardString = $customer->cards?->implode('number', ',');

            $modalBlocks = [
                Kit::section(
                    text: Kit::mrkdwnText(':white_check_mark: Waiver found'),
                ),
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
            ];
        } else {
            $modalBlocks = [
                Kit::section(
                    text: Kit::mrkdwnText(':x: No waiver found. The first name, last name, and email must all ' .
                        'match for us to find the waiver.'),
                ),
                Kit::section(
                    text: Kit::mrkdwnText("Please have the member sign the waiver by logging in to denhac.org and " .
                        "clicking the waiver button in the top menu. If their information doesn't automatically fill " .
                        "in, make sure it matches the information below *exactly*. If there is a typo in the " .
                        "information below, please reach out to a board member/web admin or email access@denhac.org " .
                        "before proceeding.")
                ),
                Kit::divider(),
                Kit::section(
                    text: Kit::mrkdwnText(
                        "*First Name:* $customer->first_name\n" .
                        "*Last Name:* $customer->last_name\n" .
                        "*Email:* $customer->email"
                    )
                ),
                Kit::divider(),
                Kit::section(
                    text: Kit::plainText("After the user has submitted a new waiver, please wait a minute or so " .
                        "and click this button:")
                ),
                Kit::actions(
                    blockId: self::REFRESH_ACTION,
                    elements: [
                        Kit::button(
                            text: Kit::plainText("Refresh"),
                            actionId: self::REFRESH_ACTION,
                        )
                    ]
                )
            ];
        }

        $this->modalView = Kit::modal(
            title: 'New Member Signup',
            callbackId: self::callbackId(),
            clearOnClose: true,
            close: 'Cancel',
            submit: 'Submit',
            privateMetadata: $customer->id,
            blocks: $modalBlocks,
        );
    }

    public static function callbackId(): string
    {
        return 'membership-new-member-id-check-modal';
    }

    public static function handle(SlackRequest $request): JsonResponse
    {
        $view = $request->payload()['view'];
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

        $idChecker = $request->customer();
        app(IdCheckMember::class)
            ->onQueue()
            ->execute(
                $customerId,
                $firstName,
                $lastName,
                $card,
                $birthday,
                $idChecker->id,
            );

        return (new NewMemberInfoModal)->update();
    }

    public static function getBlockActions(): array
    {
        return [
            self::blockActionUpdate(self::REFRESH_ACTION),
        ];
    }

    public static function onBlockAction(SlackRequest $request)
    {
        $view = $request->payload()['view'];
        $customerId = $view['private_metadata'];
        $modal = new NewMemberIdCheckModal($customerId);
        return $modal->updateViaApi($request);
    }
}
