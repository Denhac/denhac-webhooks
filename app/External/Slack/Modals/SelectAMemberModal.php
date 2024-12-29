<?php

namespace App\External\Slack\Modals;

use App\External\Slack\ClassFinder;
use App\Http\Requests\SlackRequest;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use SlackPhp\BlockKit\Collections\OptionSet;
use SlackPhp\BlockKit\Kit;

class SelectAMemberModal implements ModalInterface
{
    use ModalTrait;
    use HasExternalOptions;

    private const SELECT_A_MEMBER = 'select-a-member';

    public function __construct($callbackOrModalClass)
    {
        $callbackId = $callbackOrModalClass;
        if (str_starts_with($callbackId, 'App\\External\\Slack\\Modals')) {
            $callbackId = $callbackOrModalClass::callbackId();
        }

        $this->modalView = Kit::modal(
            title: 'Select A Member',
            callbackId: self::callbackId(),
            clearOnClose: true,
            close: 'Cancel',
            submit: 'Submit',
            privateMetadata: $callbackId,
            blocks: [
                Kit::input(
                    label: 'Member',
                    blockId: self::SELECT_A_MEMBER,
                    element: Kit::externalSelectMenu(
                        actionId: self::SELECT_A_MEMBER,
                        placeholder: 'Select a Member',
                        minQueryLength: 0
                    ),
                ),
            ],
        );
    }

    public static function callbackId(): string
    {
        return 'select-a-member-modal';
    }

    public static function handle(SlackRequest $request): JsonResponse
    {
        $selectedOption = $request->payload()['view']['state']['values'][self::SELECT_A_MEMBER][self::SELECT_A_MEMBER]['selected_option']['value'];

        $matches = [];
        $result = preg_match('/customer-(\d+)/', $selectedOption, $matches);

        if (! $result) {
            throw new \Exception("Option wasn't valid for customer: $selectedOption");
        }

        $customer_id = $matches[1];
        $nextCallbackId = $request->payload()['view']['private_metadata'];

        $modalClass = ClassFinder::getModal($nextCallbackId);
        /** @var ModalTrait $modal */
        $modal = new $modalClass($customer_id);

        return $modal->update();
    }

    public static function getExternalOptions(SlackRequest $request): OptionSet
    {
        $filterValue = $request->payload()['value'] ?? null;
        $optionSet = Kit::optionSet();

        $customers = Customer::all();

        foreach ($customers as $customer) {
            /** @var Customer $customer */
            $name = "{$customer->first_name} {$customer->last_name}";

            if(! is_null($filterValue) && ! Str::contains($name, $filterValue)) {
                continue;
            }

            if ($customer->member) {
                $text = "$name (Member)";
            } else {
                $text = "$name (Not a Member)";
            }

            $value = "customer-{$customer->id}";

            $optionSet->append(Kit::option(
                text: $text,
                value: $value,
            ));
        }

        return $optionSet;
    }
}
