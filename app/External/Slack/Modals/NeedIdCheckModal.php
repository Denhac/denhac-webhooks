<?php

namespace App\External\Slack\Modals;

use App\Http\Requests\SlackRequest;
use App\Models\Customer;
use Illuminate\Support\Facades\Log;
use SlackPhp\BlockKit\Kit;
use SlackPhp\BlockKit\Surfaces\Modal;

class NeedIdCheckModal implements ModalInterface
{
    use ModalTrait;

    private const NEW_MEMBER = 'new-member';

    private Modal $modalView;

    public function __construct()
    {
        $this->modalView = Kit::modal(
            title: 'New Member Signup',
            callbackId: self::callbackId(),
            clearOnClose: true,
            close: 'Cancel',
            submit: 'Submit',
            blocks: [
                Kit::input(
                    label: 'New Member',
                    blockId: self::NEW_MEMBER,
                    element: Kit::externalSelectMenu(
                        actionId: self::NEW_MEMBER,
                        placeholder: 'Select a Customer',
                        minQueryLength: 0
                    ),
                )
            ],
        );
    }

    public static function callbackId(): string
    {
        return 'membership-need-id-check-modal';
    }

    public static function handle(SlackRequest $request)
    {
        if (! $request->customer()->canIDcheck()) {
            Log::warning('NeedIdCheckModal: Rejecting unauthorized submission from user ' . $request->customer()->id);
            throw new \Exception('Unauthorized');
        }

        $selectedOption = $request->payload()['view']['state']['values'][self::NEW_MEMBER][self::NEW_MEMBER]['selected_option']['value'];

        $matches = [];
        $result = preg_match('/customer-(\d+)/', $selectedOption, $matches);

        if (! $result) {
            throw new \Exception("Option wasn't valid for customer: $selectedOption");
        }

        $customer_id = $matches[1];
        $modal = new NewMemberIdCheckModal($customer_id);

        return $modal->update();
    }

    public static function getOptions(SlackRequest $request)
    {
        $optionSet = Kit::optionSet();

        $customersNeedingIdCheck = Customer::with('memberships')
            ->where('id_checked', false)
            ->whereRelation('memberships', 'status', 'paused') // TODO Verify User Membership is 6410
            ->orderBy('id', 'desc')  // Latest sign ups end up appearing first
            ->get();

        foreach ($customersNeedingIdCheck as $customer) {
            /** @var Customer $customer */
            $name = "{$customer->first_name} {$customer->last_name}";

            $value = "customer-$customer->id";

            $optionSet->append(Kit::option(
                text: $name,
                value: $value,
            ));
        }

        return $optionSet;
    }

    public function jsonSerialize(): array
    {
        return $this->modalView->jsonSerialize();
    }
}
