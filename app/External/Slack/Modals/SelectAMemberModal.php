<?php

namespace App\External\Slack\Modals;

use App\Customer;
use App\External\Slack\ClassFinder;
use App\External\Slack\SlackOptions;
use App\Http\Requests\SlackRequest;
use SlackPhp\BlockKit\Kit;
use SlackPhp\BlockKit\Surfaces\Modal;

class SelectAMemberModal implements ModalInterface
{
    use ModalTrait;

    private const SELECT_A_MEMBER = 'select-a-member';

    private Modal $modalView;

    public function __construct($callbackOrModalClass)
    {
        $callbackId = $callbackOrModalClass;
        if (str_starts_with($callbackId, 'App\\External\\Slack\\Modals')) {
            $callbackId = $callbackOrModalClass::callbackId();
        }

        $this->modalView = Kit::newModal()
            ->callbackId(self::callbackId())
            ->title('Select A Member')
            ->clearOnClose(true)
            ->close('Cancel')
            ->submit('Submit')
            ->privateMetadata($callbackId);

        $this->modalView->newInput()
            ->label('Member')
            ->blockId(self::SELECT_A_MEMBER)
            ->newSelectMenu()
            ->forExternalOptions()
            ->actionId(self::SELECT_A_MEMBER)
            ->placeholder('Select a Member')
            ->minQueryLength(2);
    }

    public static function callbackId(): string
    {
        return 'select-a-member-modal';
    }

    public static function handle(SlackRequest $request)
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

    public static function getOptions(SlackRequest $request)
    {
        $options = SlackOptions::new();

        $customers = Customer::all();

        foreach ($customers as $customer) {
            /** @var Customer $customer */
            $name = "{$customer->first_name} {$customer->last_name}";

            if ($customer->member) {
                $text = "$name (Member)";
            } else {
                $text = "$name (Not a Member)";
            }

            $value = "customer-{$customer->woo_id}";

            $options->option($text, $value);
        }

        return $options;
    }

    public function jsonSerialize()
    {
        return $this->modalView->jsonSerialize();
    }
}
